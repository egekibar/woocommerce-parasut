<?php

add_action("admin_action_parasut_sync_products", function (){
	if (isset($_GET['start'])){
		$auth = Plugin\Helper\Auth::login();
		$parasut = new \Plugin\Parasut\Products($auth);
		$products = $parasut->list_products()->result->data;

//        dump($products);

		foreach ($products as $data) {
			$product_id = wc_get_product_id_by_sku($data->attributes->code);

			if ($product_id) {
				$product = wc_get_product($product_id);
			} else {
				$product = new WC_Product_Simple();
				$product->set_name($data->attributes->name);
				$product->set_description('');
			}

			$product->set_sku($data->attributes->code);
			$product->set_regular_price($data->attributes->list_price);
			$product->set_tax_class('standard');
			$product->set_tax_status('taxable');
			$product->set_manage_stock(true);
			$product->set_stock_quantity($data->attributes->stock_count);
			$product->set_backorders("yes");

			if (isset($data->attributes->photo->url)) {
				$image_url = $data->attributes->photo->url;
				$existing_attachment_id = attachment_url_to_postid($image_url);
				if (!$existing_attachment_id) {
					$image_name = basename($image_url);
					$upload_dir = wp_upload_dir();
					$image_data = file_get_contents($image_url);
					$filename = $upload_dir['path'] . '/' . $image_name;
					file_put_contents($filename, $image_data);
					$file_type = wp_check_filetype($filename, null);
					$attachment = array(
						'post_mime_type' => $file_type['type'],
						'post_title' => sanitize_file_name($image_name),
						'post_content' => '',
						'post_status' => 'inherit'
					);
					$attach_id = wp_insert_attachment($attachment, $filename);
					if (!is_wp_error($attach_id)) {
						require_once(ABSPATH . 'wp-admin/includes/image.php');
						$attach_data = wp_generate_attachment_metadata($attach_id, $filename);
						wp_update_attachment_metadata($attach_id, $attach_data);
						set_post_thumbnail($product_id, $attach_id);
					}
				} else {
					set_post_thumbnail($product_id, $existing_attachment_id);
				}
			}

			$product_id = $product->save();

			if (is_wp_error($product_id)) {
				$error_message = $product_id->get_error_message();
				echo "Product update/insert failed: $error_message";
			}

			if ($id = $data->relationships->category->data->id)
				$category = (new \Plugin\Parasut\Categories($auth))->show($id)->result->data->attributes->name;

			if (taxonomy_exists('product_group'))
				wp_set_object_terms($product_id, $category, 'product_group');

			update_post_meta($product_id, "parasut_urun_id", $data->id);
			update_post_meta($product_id, "parasut_vergi_orani", $data->attributes->vat_rate);

//			dd($product_id, $product, $data);
		}

		die;
	}

	view("modal", [
		"title" => "Ürünler Kolaybi'den sisteme aktarılacak.",
		"ajax_action" => "sync_products"
	]);
});

// ThickBox Support
add_action ('admin_head-edit.php', function () {
    global $typenow;
    if ($typenow == "product"):
        add_thickbox();
    endif;
});

// Header Button
add_action('admin_footer-edit.php', function () {
    global $typenow;
    if ($typenow == "product"): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            jQuery(document).ready(function($) {
                $('<a>').attr('href', 'admin.php?action=parasut_sync_products&TB_iframe=true')
                    .addClass('page-title-action thickbox')
                    .text('Ürünleri Senkronize Et')
                    .insertBefore('.wp-header-end');

                $(document).on('tb_unload', function() {
                    location.reload()
                });
            });
        });
    </script>
    <?php endif;
});

//add_action('woocommerce_product_set_stock', function ($product) {
//    dd($product, $product->get_stock_quantity());
//}, 10, 3);
