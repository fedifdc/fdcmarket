<?php if (!defined('IS_IN_SCRIPT')) { die();  exit; }
$options = get_option('cb_pengaturan');
$blogurl = home_url();
$status = $wpdb->get_row("SELECT * FROM `cb_produklain`,`wp_member` 
	WHERE `cb_produklain`.`id`=".$id2up." AND `wp_member`.`idwp`=`cb_produklain`.`idwp`");
$id_sponsor = $status->id_referral;
$sponsorkita = $wpdb->get_row("SELECT * FROM `wp_member` WHERE `idwp`=".$id_sponsor);

//echo 'ID SPONSOR :'. $sponsorkita->nama;
function ubahemail($p) {
	global $status, $sponsorkita, $blogurl, $nama, $username;	
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
	// Data sponsor
	$p = str_replace("{{namasponsor}}", $sponsorkita->nama, $p);
	$p = str_replace("{{hpsponsor}}", $sponsorkita->telp, $p);
	$p = str_replace("{{emailsponsor}}", $sponsorkita->email, $p);
	return $p;
}

if ($status->status == 0) {
	$idwp = $status->idwp;	
	$nama = $status->nama;
	$email = $status->email;
	$username = $status->username;
	$idproduk = $status->idproduk;
	$affiliasi = urlaff($status->subdomain);
	//$point_type = 'mytype';
	//$mycred     = mycred( $point_type );
	$user_id = $status->idwp;
	$yith = YITH_WC_Points_Rewards();
    $points = 2000000;


	if ($idproduk==0) {
		$kredit = $options['harga'];	
		$wpdb->query("UPDATE `wp_member` SET `membership`='2', `tgl_upgrade`=NOW() WHERE `idwp`=".$idwp);
		$namaproduk = 'Upgrade Premium';
	
	// custom By Fedi tambah poin saat upgrade
	  
		$yith->add_point_to_customer( $user_id, $points,'admin_action', 'Upgrade Premium');
		
		 /*Make sure user is not excluded
		if ( ! $mycred->exclude_user( $user_id ) ) {

		// get users balance
		$balance = $mycred->get_users_balance( $user_id );

		// Adjust balance with a log entry
		$mycred->add_creds(
		'Upgrade Premium',
		$user_id,
		2000000,
		'Poin Upgrade Telah Di Tambahkan!'
			);
		}
	 end custom*/
	} else {
		$dataproduk = $wpdb->get_row("SELECT `nama`,`harga` FROM `cb_produk` WHERE `id`=".$idproduk);	
		$kredit = $dataproduk->harga;
		$namaproduk = $dataproduk->nama;	
	}

	$wpdb->query("UPDATE `cb_produklain` SET `status`=1, `tgl_bayar`=NOW() WHERE `id`=".$id2up);
	
	// Kirim email aktifasi dan laporan
	$header = 'From: '.get_option('nama_email').' <'.get_option('alamat_email').'>';	
	$body = ubahemail(get_option('isi_email_aktif'));
	$subject = ubahemail(get_option('judul_email_aktif'));
	if (isset($thebank) && $thebank != '') {
		$headerlapor = 'From: WP-Affiliasi <noreply@'.$_SERVER['SERVER_NAME'].'>';
		$bodylapor = '
Lapor Bos,

Ada yang melakukan pembayaran otomatis. Berikut datanya:

Nama : '.$status->nama.'
Username : '.$status->username.'
Alamat : '.$status->alamat.' '.$status->kota.' '.$status->provinsi.'
Produk : '.$namaproduk.' 
Pembayaran : '.$thebank.'

Silahkan melakukan pengecekan jika diperlukan. Transaksi juga telah dicatat di laporan
		';				
	}
	if (function_exists('wp_mail')) {
		wp_mail($email, $subject, $body, $header);
		if ($thebank) {
			wp_mail(get_option('alamat_email'), 'Upgrade Otomatis Sukses', $bodylapor, $headerlapor);
		}
	}


	$custom = unserialize($status->homepage);
	if (!isset($custom['uplines'])) {
		$custom['uplines'] = cbaff_uplines($status->id_referral);
		$customdb = serialize($custom);
		$wpdb->query("UPDATE `wp_member` SET `homepage`='".$customdb."' WHERE `idwp`=".$idwp);
	}
	$iduplines = $custom['uplines'];
	$uplines = $wpdb->get_results("SELECT * FROM `wp_member` WHERE `idwp` IN (".$iduplines.") ORDER BY FIELD(`idwp`,".$iduplines.")");
	$komisi = get_option('komisi');
	$i = 0;
	foreach ($uplines as $upline) {
		if ($upline->idwp != 0) {
			if ($komisi['pps'][$i]['free']==0 && $upline->membership==1) {
				// Lewati
			} else {
				if ($idproduk == 0) {
					if ($upline->membership == 2) {
						$jmlkomisi = ($komisi['pps'][$i]['premium']/100)*$kredit;
					} else {
						$jmlkomisi = ($komisi['pps'][$i]['free']/100)*$kredit;
					}
				} else {
					if ($upline->membership == 2) {
						if (isset($komisi['pps'][$i]['lainpremium']) && $komisi['pps'][$i]['lainpremium'] > 0) {
							$jmlkomisi = ($komisi['pps'][$i]['lainpremium']/100)*$kredit;
						} else {
							$jmlkomisi = ($komisi['pps'][$i]['premium']/100)*$kredit;
						}
					} else {
						if (isset($komisi['pps'][$i]['lainfree']) && $komisi['pps'][$i]['lainfree'] > 0) {
							$jmlkomisi = ($komisi['pps'][$i]['lainfree']/100)*$kredit;
						} else {
							$jmlkomisi = ($komisi['pps'][$i]['free']/100)*$kredit;
						}
					}
				}
				

				$id_referral = $upline->idwp;

				if ($i==0) {
					$wpdb->query("UPDATE `wp_member` SET `downline_lngsg`=`downline_lngsg`+1,`jml_downline`=`jml_downline`+1,`jml_voucher`=`jml_voucher`+".$jmlkomisi.", `sisa_voucher`=`sisa_voucher`+".$jmlkomisi." WHERE `idwp` = ".$id_referral);

					$upemail = $upline->email;
					$datamember = $status;
					$status = $upline;
					$body = ubahemail(get_option('isi_email_sale'));
					$subject = ubahemail(get_option('judul_email_sale'));
					$header = 'From: '.get_option('nama_email').' <'.get_option('alamat_email').'>';	

					if (function_exists('wp_mail')) {
					wp_mail($upemail, $subject, $body, $header);
					}
				} else {
					$wpdb->query("UPDATE `wp_member` SET `jml_downline`=`jml_downline`+1,`jml_voucher`=`jml_voucher`+".$jmlkomisi.", `sisa_voucher`=`sisa_voucher`+".$jmlkomisi." WHERE `idwp` = ".$id_referral);
				}

				if ($jmlkomisi > 0) {
					$transaksi = 'Komisi Lvl '.($i+1).' Order: '.$namaproduk.' oleh: '.$datamember->nama.' (ID: '.$datamember->idwp.')';
					$wpdb->query("INSERT INTO `cb_laporan` (`tanggal`,`transaksi`,`kredit`,`komisi`,`keterangan`,`id_user`,`id_sponsor`,`id_order`) VALUES (NOW(),'".$transaksi."',".$kredit.",".$jmlkomisi.",'cbaff',".$idwp.",".$id_referral.",".$id2up.")");
				}
				$i++;
			}
		}
	}

	// Pekerjaan Selesai, kirim pendaftaran ke Autoresponder dan buat respon
	if (isset($options['action2']) && $options['action2']) {
		echo '<form method="POST" name="result" action="'.$options['action2'].'">';
		for ($i=10; $i<20; $i++) {
			$value = $options['value'][$i];
			$value = str_replace('{{nama}}',$nama,$value);
			$value = str_replace('{{email}}',$email,$value);
			$value = str_replace('{{username}}',$username,$value);
			$value = str_replace('{{password}}',$password,$value);
			$value = str_replace('{{affiliasi}}',$affiliasi,$value);
			echo '<input type="hidden" name="'.$options['field'][$i].'" value="'.$value.'"/>';
		}
		echo '</form>
		<script type="text/javascript">
		document.result.submit();
		</script>
		';
	} else {
		echo '<div id="message2" class="notice notice-success is-dismissible"><p>Order sudah diproses!</p></div>';
	}
	
} else {
	echo '<div id="message2" class="notice notice-warning is-dismissible"><p>Produk sudah dibayar</p></div>';
}