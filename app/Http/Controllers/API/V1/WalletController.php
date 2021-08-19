<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\WalletDepositRequest;
use App\Http\Requests\API\V1\WalletTransferRequest;
use App\Models\API\V1\Wallet;
use App\Repositories\WalletRepository;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    protected $walletRepository;

    public function __construct(WalletRepository $walletRepository)
    {
        $this->walletRepository = $walletRepository;
    }

    public function walletDeposit(WalletDepositRequest $request)
    {
        return $this->walletRepository->walletDeposit($request);
    }

    public function walletTransfer(WalletTransferRequest $request)
    {
        return $this->walletRepository->walletTransfer($request);
    }

    public function walletDetails()
    {
        return $this->walletRepository->walletDetails();
    }
}
