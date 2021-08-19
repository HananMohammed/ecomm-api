<?php


namespace App\Repositories;


use App\Interfaces\WalletInterface;
use App\Models\API\V1\User;
use App\Models\API\V1\Wallet;
use App\Models\API\V1\WalletTransfer;
use App\Traits\GeneralTrait;
use App\Traits\ResponseTrait;
use Cartalyst\Stripe\Laravel\Facades\Stripe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WalletRepository implements WalletInterface
{
    use ResponseTrait; use GeneralTrait;

    protected $paginate_count = 10;

    public function walletDeposit($request)
    {
        $user = User::find(Auth::user()->id);
        $wallet_auth = Hash::check(request('wallet_password'), $user->wallet_password);
        if ($wallet_auth){
            $walletMoney = Wallet::where('user_id', $user->id)->pluck('money')[0];
            if ($request->payment_gateway == 'strip'){
                if (app()->environment('production')){
                    $token = Stripe::tokens()->create([
                        'card' => [
                            'number'    => $request->card,
                            'exp_month' => $request->ccExpiryMonth,
                            'exp_year'  => $request->ccExpiryYear,
                            'cvc'       => $request->cvvNumber,
                        ],
                    ]);

                    $charge = Stripe::charges()->create([
                        'amount' => $request->money,
                        'currency' => 'CAD',
                        'source' => $request->stripeToken,
                        'description' => 'Order',
                        'receipt_email' => $request->email,
                    ]);
                }

                $money = $walletMoney + $request->money;

                $walletUpdated = Wallet::where('user_id', $user->id)
                                       ->update([ 'money' => $money ]);
                if ($walletUpdated){
                    return $this->returnData('data', ['balance' => $money], 'Money Deposits Successfully To Balance');
                }
            }else{
                return $this->returnError(403, __('messages.payment_not_support'));
            }
        }else{
            return $this->returnError(403, __('errors.wrong_wallet_pass'));
        }
    }

    public function walletTransfer($request)
    {
        $user = User::find(Auth::user()->id);
        $wallet_auth = Hash::check(request('wallet_password'), $user->wallet_password);
        $transfer_to_user = User::where('email', request('user_email'))->pluck('id')->toArray();
        if ($wallet_auth){
            if (!empty($transfer_to_user)){
                if ($user->email == request('user_email')){
                    return $this->returnError(403, __('errors.wrong_transfer'));
                }else{
                    $walletMoney = Wallet::where('user_id', $user->id)->get()->toArray();
                    if ($walletMoney[0]['transfer_status'] == 1){
                        $tranferUser_money =  Wallet::where('user_id', $transfer_to_user[0])->get()->toArray();
                        if ($tranferUser_money[0]['transfer_status']==1){
                            if ($walletMoney[0]['money'] >= request('money')){
                                //auth_wallet
                                Wallet::where('user_id', $user->id)
                                    ->update([ 'money' => $walletMoney[0]['money'] - $request->money ]);

                                Wallet::where('user_id', $transfer_to_user[0])
                                    ->update([ 'money' => $tranferUser_money[0]['money'] + $request->money ]);

                                WalletTransfer::create( [
                                    'transfer_from' => $user->id,
                                    'transfer_to'   =>$transfer_to_user[0],
                                    'value' => request('money')
                                ]);

                            return $this->returnSuccessMessage('Money Transferred Successfully');

                            }else{
                                return $this->returnError(403, __('errors.balance_issue'));
                            }
                        }else{
                            return $this->returnError(403, __('errors.transfer_user_stopped'));
                        }

                    }else{
                        return $this->returnError(403, __('errors.transfer_stopped'));
                    }
                }
            }else{
                return $this->returnError(403, __('errors.user_not_exist'));
            }
        }else{
            return $this->returnError(403, __('errors.wrong_wallet_pass'));
        }
    }

    public function walletDetails()
    {
        try {
            $user = User::find(Auth::user()->id);
            $walletMoney = Wallet::where('user_id', $user->id)->get()->toArray();

            $transfer_out = WalletTransfer::select('users.email', 'users.name', 'wallet_transfers.value')
                ->join('users', 'wallet_transfers.transfer_to', '=', 'users.id')
                ->where('wallet_transfers.transfer_from', $user->id)
                ->get();
            $transfer_in  =  WalletTransfer::select('users.email', 'users.name', 'wallet_transfers.value')
                ->join('users', 'wallet_transfers.transfer_from', '=', 'users.id')
                ->where('wallet_transfers.transfer_to', $user->id)
                ->get();

            $wallet_actions = [
                "wallet_balance" => $walletMoney[0]['money'],
                "wallet_transfer_out" => $transfer_out,
                "wallet_transfer_in" => $transfer_in
            ];

            return $this->returnData("wallet", $wallet_actions, "Wallet Details Returned Successfully");

        }catch (\Exception $exception){
            return $this->returnError(500, __('errors.server_error'));
        }
    }
}
