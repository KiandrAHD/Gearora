<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Kontrak domain yang dapat dibayar melalui gateway Snap (rental order maupun
 * sales order). Dipakai agar adapter Midtrans tidak terikat pada satu domain
 * (lihat ADR_SHARED_PAYMENT_FOR_RETAIL.md). Hanya data tampilan yang dibutuhkan
 * provider yang diekspos; angka otoritatif tetap berasal dari PaymentAttempt.
 */
interface PayableOrder
{
    /** Kode order untuk logging & referensi. */
    public function payableCode(): string;

    /** Pelanggan tujuan notifikasi provider. */
    public function payableCustomer(): ?User;

    /**
     * Rincian item Snap: setiap elemen berisi id, price (integer Rupiah),
     * quantity, dan name.
     *
     * @return array<int, array{id:string,price:int,quantity:int,name:string}>
     */
    public function payableItemDetails(): array;
}
