<?php

/*

Bismillahirrahmaanirrahiim

Alhamdulillahirobbil 'alamiin



Plugin Name: WP Affiliasi with BV Price

Plugin URI: https://cafebisnis.com

Description: Plugin untuk memberi perhitungan BV produk sebelum perhitungan komisi

Version: 1.0

Author: Lutvi Avandi

Author URI: https://lutviavandi.com

*/



add_action('woocommerce_order_status_completed', 'mp_processorder');



function mp_processorder($order_id)

{

	global $wpdb, $user_ID;

	if (!function_exists('gantiisi')) {

	    function gantiisi($p) {

			global $status, $blogurl, $nama, $username;

			$urlaffiliasi = urlaff($status->subdomain);

			$p = str_replace("{{namamember}}", $status->nama, $p);

			$p = str_replace("{{username}}", $status->username, $p);

			$p = str_replace("{{password}}", $status->password, $p);

			$p = str_replace("{{urlreseller}}", $urlaffiliasi, $p);

			$p = str_replace("{{telpmember}}", $status->telp, $p);

			$p = str_replace("{{kotamember}}", $status->kota.' '.$status->provinsi, $p);

			$p = str_replace("{{namabank}}", $status->bank, $p);

			$p = str_replace("{{rekening}}", $status->rekening, $p);

			$p = str_replace("{{atasnama}}", $status->ac, $p);

			$p = str_replace("{{namaprospek}}", $nama, $p);

			$p = str_replace("{{usernameprospek}}", $username, $p);

			return $p;

		}

	}

	$komisi = get_option('komisi');

	$i = 0;

	$id_referral = '';



	// Data Order 



	$order = new WC_Order( $order_id );

	$id_user = $order->customer_user;

    $id_sponsor = get_post_meta($order_id,'Sponsor ID',true);    

	$laporan = '';	



	// Data sponsor



	$status = $wpdb->get_row("SELECT * FROM `wp_member` WHERE `idwp`=".$id_sponsor);    

	$custom = unserialize($status->homepage);

	if (!isset($custom['uplines'])) 

	{

		$custom['uplines'] = cbaff_uplines($status->id_referral);

		$customdb = serialize($custom);

		$wpdb->query("UPDATE `wp_member` SET `homepage`='".$customdb."' WHERE `idwp`=".$id_sponsor);

	}



	if ($custom['uplines'] != 0) 

	{

		$iduplines = $id_sponsor.','.$custom['uplines'];

		$uplines = $wpdb->get_results("SELECT * FROM `wp_member` WHERE `idwp` IN (".$iduplines.") 

			ORDER BY FIELD(`idwp`,".$iduplines.")");

	} 

	else 

	{

		$uplines = $wpdb->get_results("SELECT * FROM `wp_member` WHERE `idwp`=".$id_sponsor);

	}



	// Bagikan komisi ke upline



	foreach ($uplines as $upline) 

	{

		if ($upline->idwp != 0) 

		{

			$totalkomisi = 0;

			$jmlkomisi = 0;		    

		    

	    	if ($upline->membership == 1) 

	    	{

	    		if ($komisi['pps'][$i]['woofree'] > 0) 

				{

					$jmlkomisi = $komisi['pps'][$i]['woofree'] / 100;

				} 

				elseif ($komisi['pps'][$i]['free'] > 0) 

				{

					$jmlkomisi = $komisi['pps'][$i]['free'] / 100;

				}

	    	} 

	    	elseif ($upline->membership == 2) 

	    	{

	    		if ($komisi['pps'][$i]['woopremium'] > 0) 

				{

					$jmlkomisi = $komisi['pps'][$i]['woopremium'] / 100;

				} 

				elseif ($komisi['pps'][$i]['premium'] > 0) 

				{

					$jmlkomisi = $komisi['pps'][$i]['premium'] / 100;

				}

	    	}



	    	$id_referral = $upline->idwp;



			if (isset($jmlkomisi) && $jmlkomisi > 0) 

			{

				// Hitung komisi tiap produk dlm order

				$order = wc_get_order( $order_id );

				foreach ($order->get_items() as $item_key => $item )

				{

					$komisiperitem = 0; // Reset komisi per item

					$namaproduk = $item->get_name();

				    $jmlproduk = $item->get_quantity();

				    $hrgproduk = $item->get_total();

				    $idproduk = $item->get_product_id();

			    	$bv = get_post_meta( $idproduk, 'BV', true );

			    	$komisiperitem = $jmlkomisi*($hrgproduk*($bv/100));

			    	// Buat Laporan Komisi

					if ($komisiperitem > 0)

					{

						$transaksi = 'Penjualan '.$namaproduk.' Level '.($i+1);

						$laporan .= "(NOW(),'".$transaksi."',".$hrgproduk.",".$komisiperitem.",'woobv',".$id_user.",".$id_referral.",".$order_id."),";

						$totalkomisi = $totalkomisi + $komisiperitem;

					}

				} // end foreach products

				

			}



			// Update data upline

			if ($i==0) 

			{

				$wpdb->query("UPDATE `wp_member` SET `downline_lngsg`=`downline_lngsg`+1,`jml_downline`=`jml_downline`+1,`jml_voucher`=`jml_voucher`+".$totalkomisi.", `sisa_voucher`=`sisa_voucher`+".$totalkomisi." WHERE `idwp` = ".$id_referral);



				$upemail = $upline->email;

				$body = gantiisi(get_option('isi_email_sale'));

				$subject = gantiisi(get_option('judul_email_sale'));

				$header = 'From: '.get_option('nama_email').' <'.get_option('alamat_email').'>';	



				if (function_exists('wp_mail')) 

				{

					wp_mail($upemail, $subject, $body, $header);

				}

			} 

			else 

			{

				$wpdb->query("UPDATE `wp_member` SET `jml_downline`=`jml_downline`+1,`jml_voucher`=`jml_voucher`+".$totalkomisi.", `sisa_voucher`=`sisa_voucher`+".$totalkomisi." WHERE `idwp` = ".$id_referral);

			}



			

			$i++; // Update level

		}

	} // end foreach uplines  



	// Input Laporan ke Database
	

	//echo "INSERT INTO `cb_laporan` (`tanggal`,`transaksi`,`kredit`,`komisi`,`keterangan`,`id_user`,`id_sponsor`,`id_order`) VALUES ".substr($laporan, 0,-1);

	if ($laporan != '') {

		$wpdb->query("INSERT INTO `cb_laporan` (`tanggal`,`transaksi`,`kredit`,`komisi`,`keterangan`,`id_user`,`id_sponsor`,`id_order`) VALUES ".substr($laporan, 0,-1));



		if($wpdb->last_error !== '')			

		{

			$wpdb->print_error();			

		}

	}

}



