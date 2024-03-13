<?php

if (isset($_GET['page']) && isset($_GET['action']) && $_GET['action'] === 'edit' && $_GET['page'] === 'wc-orders') {
	add_action ('admin_head', function () {
		add_thickbox();
		$order = wc_get_order($_GET['id']);
		$uuid = $order->get_meta("e_invoice_uuid", true);
		$doc = $order->get_meta("document_id", true);
        ?> <script>
			document.addEventListener('DOMContentLoaded', function() {
                jQuery(document).ready(function($) {
                    <?php if ($doc && !$uuid): ?>
                        $('<li>').addClass('wide').html(
                            $('<a>').attr('href', 'admin.php?action=parasut_convert_e_invoice&id=<?= $_GET['id'] ?>&TB_iframe=true')
                            .addClass('button thickbox')
                            .css({ width: "100%" })
                            .text('E-fatura Oluştur')
                        ).insertAfter('#actions')

                        $(document).on('tb_unload', function() {
                            location.reload()
                        });
                    <?php elseif($doc && $uuid): ?>
                        $('<li>').addClass('wide').html(
                            $('<a>').attr('href', '/?e_invoice_uuid=<?= $uuid ?>').attr('target', '_blank')
                            .addClass('button')
                            .css({ width: "100%" })
                            .text("E-fatura'yı Görüntüle")
                        ).insertAfter('#actions')
                    <?php else: ?>
                        $('<li>').addClass('wide').html(
                            $('<a>').attr('href', 'admin.php?action=parasut_create_invoice&id=<?= $_GET['id'] ?>&TB_iframe=true').attr('target', '_blank')
                                .addClass('button thickbox')
                                .css({ width: "100%" })
                                .text("Fatura Oluştur")
                        ).insertAfter('#actions')

                        $(document).on('tb_unload', function() {
                            location.reload()
                        });
                    <?php endif; ?>
                });
			});
		</script> <?php
	});
}

//add_action("admin_action_parasut_view_e_invoice", function (){
//	$order_id = $_GET['id'];
//	if ($order_id){
//		\Plugin\Kolaybi\Authorization::login();
//		$order = wc_get_order($order_id);
//		$uuid = $order->get_meta("e_invoice_uuid", true);
//		$resp = \Plugin\Kolaybi\Order::view_e_invoice($uuid);
//
//        if ($resp->output_type == "pdf")
//		    echo "<img src='data:application/pdf;base64,{$resp->src}' style='width: 100%'>";
//	}
//});

//add_action('woocommerce_order_status_changed', function ($order_id, $old_status, $new_status){
//	if ($new_status == "completed") do_action('parasut_create_invoice', $order_id);
//}, 10, 3);

add_action("admin_action_parasut_create_invoice", function (){
    if ($_GET['start']){
	    echo do_action("parasut_create_invoice", $_GET['id']);
        die;
    }
	view("modal", [
		"title" => "Fatura Paraşüt'e aktarılacak.",
		"ajax_action" => "create_invoice&id=".$_GET['id']
	]);
});

add_action("parasut_create_invoice", function ($order_id){
	$auth = Plugin\Helper\Auth::login();

	$parasut = new \Plugin\Parasut\Tags($auth);
	$tags = $parasut->list_tags()->result->data;

	foreach ($tags as $tag)
		if ($tag->attributes->name == "B2B")
			$tag_id = $tag->id;

	if (empty($tag_id))
		$tag_id = $parasut->create([
			"data" => [
				"type" => "tags",
				"attributes" => [
					"name" => "B2B"
				]
			]
		])->result->data->id;

	$order = wc_get_order($order_id);

	$data["data"] = [
        "type" => "sales_invoices",
        "attributes" => [
	        "item_type" => "invoice",
	        'description' => "Sipariş No: $order_id",
	        "issue_date" => $order->get_date_modified()->date('Y-m-d h:i:s'), //düzenleme tarihi
	        "invoice_no" => $order_id,
	        "invoice_id" => $order_id,
	        "order_no" => $order_id,
	        "order_date" => $order->get_date_created()->date('Y-m-d h:i:s'),
	        "currency" => $order->get_currency() == "TRY" ? "TRL" : $order->get_currency(), //döviz tipi // TRL, USD, EUR, GBP
	        "shipment_included" => false,
	        "billing_address" => str_replace(["<br/>", "<br>"], " ", $order->get_formatted_billing_address()),
	        "billing_phone" => $order->get_billing_phone(),
	        "country" => WC()->countries->countries[$order->get_billing_country()],
	        "city" => $order->get_billing_city(),
	        "tax_number" => get_user_meta($order->get_user_id(), 'tax_number', true),
	        "tax_office" => get_user_meta($order->get_user_id(), 'tax_office', true),
        ],
        "relationships" => [
            "details" => [
                "data" => [ ],
            ],
            "contact" => [
                "data" => [
                    "type" => "contacts",
                    "id" => $order->get_user()->nickname
                ]
            ],
            'tags' => [
	            'data' => [
		            [
			            'id' => $tag_id,
			            'type' => 'tags'
		            ]
	            ]
            ]
        ]
	];

	foreach ( $order->get_items() as $item )
        $products[] = [
	        'type' => 'sales_invoice_details',
	        'attributes' => array(
		        'quantity' => $item->get_quantity(),
		        'unit_price' => $item->get_subtotal() / $item->get_quantity(),
		        'vat_rate' => get_post_meta($item->get_product_id(), 'parasut_vergi_orani', true),
		        'description' => $item->get_name()
	        ),
	        'relationships' => array(
		        'product' => array(
			        'data' => array(
				        'id' => get_post_meta($item->get_product_id(), 'parasut_urun_id', true),
				        'type' => 'products'
			        )
		        )
	        )
        ];

    $data["data"]["relationships"]["details"]["data"] = $products;

	$parasut = new \Plugin\Parasut\Invoices($auth);
	$invoice = $parasut->create($data);

    if ($invoice->code == 422)
        abort(422, str_replace("Unprocessable Entity: ", "", $invoice->error_message));

	$order->update_meta_data("document_id", $invoice->result->data->id);
	$order->save();
});

add_action("admin_action_parasut_convert_e_invoice", function (){
	if (isset($_GET['start'])){
		$order = wc_get_order($_GET['id']);
		if ($order):
			$auth = Plugin\Helper\Auth::login();
			$parasut = new \Plugin\Parasut\Invoices($auth);
			$tax_number = get_user_meta($order->get_user_id(), "tax_number", true);

			$vknData = $parasut->check_vkn_type($tax_number);
			$eInvoiceAddress = $vknData->result->data[0]->attributes->e_invoice_address;

			if (empty($eInvoiceAddress)):
				$data["data"] = [
					"type" => "e_archives",
					"relationships" => [
						"sales_invoice" => [
							"data" => [
								"id" => $order->get_meta("document_id", true),
								"type" => "sales_invoices"
							]
						]
					]
				];
				$invoice = $parasut->create_e_archive($data);
			else:
				$data["data"] = [
					"type" => "e_invoices",
					"attributes" => [
						'scenario' => 'basic',
						'to' => $eInvoiceAddress
					],
					"relationships" => [
						"invoice" => [
							"data" => [
								"id" => $order->get_meta("document_id", true),
								"type" => "sales_invoices"
							]
						]
					]
				];
				$invoice = $parasut->create_e_invoice($data);
			endif;

			if ($invoice->code == 201) {
				$order->update_meta_data( "e_invoice_id", $invoice->result->data->id );

				if ($invoice->result->data->links->self) {
					$note = json_decode(wp_remote_get('https://api.parasut.com/v4/'.$invoice->result->data->links->self)['body'], true)['data']['attributes']['errors'][0];
					if (!empty($note)) $order->add_order_note( $note );
				}

				$order->save();
            } else {
				http_response_code($invoice->code);
				dd($invoice);
			}
        else:
            abort(404, "Sipariş bulunamadı.");
		endif;
	}
	view("modal", [
		"title" => "Bu faturadan e-fatura oluştur.",
		"ajax_action" => "convert_e_invoice&id={$_GET['id']}"
	]);

});

add_filter('woocommerce_account_orders_columns', function ( $columns ){
	$order_actions  = $columns['order-actions'];
	unset($columns['order-actions']);
	$columns['e-fatura'] = "E-fatura";
	$columns['order-actions'] = $order_actions;
	return $columns;
}, 10, 1 );

add_action('woocommerce_my_account_my_orders_column_e-fatura', function ( $order ) {
	if ( $value = $order->get_meta( 'e_invoice_no', true ) ) {
        echo "<a target='_blank' href='/?e_invoice_uuid={$order->get_meta( 'e_invoice_uuid', true )}'>{$value}</a>";
	} else {
		printf( '<small>%s</small>', __("Oluşturulmadı") );
	}
});

//add_action('init', function () {
//	add_rewrite_endpoint( 'e-fatura', EP_PAGES );
//});
//
//add_action('wp', function () {
//	if (wc_get_page_id('myaccount') == get_queried_object_id())
//		flush_rewrite_rules();
//});
//
//add_action('woocommerce_account_e-fatura_endpoint', function () {
//	echo do_action('admin_action_parasut_view_e_invoice');
//});

add_action("wp", function (){
    if (isset($_GET['e_invoice_uuid'])) {
	    \Plugin\Kolaybi\Authorization::login();
	    $resp = \Plugin\Kolaybi\Order::view_e_invoice($_GET['e_invoice_uuid']);
	    if ($resp->output_type == "pdf")
		    header("Content-type:application/pdf");
        if ($resp->src)
	        die( base64_decode($resp->src) );
    }
});