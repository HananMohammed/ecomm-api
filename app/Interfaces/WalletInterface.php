<?php


namespace App\Interfaces;


use PHPUnit\Util\Json;

interface WalletInterface
{
    /** Deposit in Wallet.
     * @param $request
     * @return json
     */
    public function walletDeposit($request);

    /** wallet Transfer
     * @param $request
     * @return mixed
     */
    public function walletTransfer($request);

    /** wallet Details
     * @return mixed
     */
    public function walletDetails();
}
