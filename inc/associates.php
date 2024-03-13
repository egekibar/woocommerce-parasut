<?php

add_shortcode('associates', function (){
	$user = wp_get_current_user();
	$auth = Plugin\Helper\Auth::login();

	$parasut = new \Plugin\Parasut\Invoices($auth);
	$invoices = $parasut->search([
		"contact_id" => $user->display_name
	])->result->data;

	$parasut = new \Plugin\Parasut\PurchaseBills($auth);
	$purchablebills = $parasut->search([
		"supplier_id" => $user->display_name
	])->result->data;

    if (!$invoices && !$purchablebills)
        return "Cari bulunamadı.";

	$all_invoices = array_merge($invoices, $purchablebills);

	usort($all_invoices, function ($a, $b){
		$timeA = strtotime($a->attributes->created_at);
		$timeB = strtotime($b->attributes->created_at);
		if ($timeA == $timeB) return 0;
		return ($timeA > $timeB) ? -1 : 1;
    });

    $prices = ["borc" => 0, "alacak" => 0];
	foreach ($all_invoices as $item)
        if ($item->attributes->payment_status != "paid")
            if ($item->type == "sales_invoices"):
                $prices["borc"] += $item->attributes->net_total_in_trl;
            else:
                $prices["alacak"] += $item->attributes->net_total_in_trl;
            endif;

//    dump($prices, $all_invoices)

	?>
        <style>
            @media screen and (min-width: 800px) {
                .table-totals {
                    width: 20rem;
                    float: right;
                    /*font-size:14px;*/
                }
            }
            .table-totals tr>td:last-child{
                text-align: end;
            }
        </style>

		<table>
			<tr>
				<th>Tarih</th>
                <th>Vade Tarihi</th>
                <th>Ödeme Durumu</th>
                <th>İşlem Türü</th>
				<th>Tutar</th>
<!--				<th></th>-->
			</tr>
			<?php foreach ($all_invoices as $data): $invoice = $data->attributes ?>
				<tr>
					<td><?= convert_date($invoice->created_at) ?></td>
                    <td><?= convert_date($invoice->due_date) ?></td>
                    <td><?= $invoice->payment_status == "paid" ? "Ödendi" : "Ödenmedi" ?></td>
                    <td><?= $data->type == "sales_invoices" ? "Borç" : "Alacak" ?></td>
					<td><?= wc_price($invoice->net_total_in_trl) ?></td>
<!--					<td style="text-align: end">-->
<!--						<a href="#" class="button">Detay</a>-->
<!--					</td>-->
				</tr>
			<?php endforeach; ?>
		</table>

        <table class="table-totals">
            <tr>
                <td><b>Toplam Borç</b></td>
                <td><?= wc_price($prices['borc']) ?></td>
            </tr>
            <tr>
                <td><b>Toplam Alacak</b></td>
                <td><?= wc_price($prices['alacak']) ?></td>
            </tr>
            <tr>
                <td><b>Toplam Bakiye</b></td>
                <td><?= wc_price($prices['alacak'] - $prices['borc']) ?></td>
            </tr>
        </table>
	<?php
});