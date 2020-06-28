<?php
if (!defined('IS_IN_SCRIPT')) { die();  exit; } 
if (!isset($user_ID) || $user_ID == 0) { 
	$refer = $_SERVER['REQUEST_URI'];
	header("Location: ".wp_login_url($refer));
}
$options = get_option('cb_pengaturan');
$harga = 0;
if (isset($_GET['bank']) && isset($_GET['order']) && is_numeric($_GET['order'])) {
	$idorder = $_GET['order'];
	$trx_id = $options['key_trx'].$idorder.'XX';
	$harga = 0;
	$cekorder = $wpdb->get_row("SELECT * FROM `cb_produklain` WHERE `id`=$idorder AND `status`=0");
	if (!empty($cekorder) && $cekorder->idproduk == 0) {
		$harga = $options['harga'];		
	} else {
		$idproduk = $cekorder->idproduk;
		$cekharga = $wpdb->get_var("SELECT `harga` FROM `cb_produk` WHERE `id`=$idproduk");
		if (!empty($cekharga)) {
			$harga = $cekharga;
		}
	}
	
	// $showtxt .= 'BERITA: '.$trx_id.'<br/>ID Order:'.$idorder.'<br/>Harga:'.$harga;
	
	if (isset($_GET['bank']) && $_GET['bank'] == 'bca') {
		$data2 = getBCA();
		$trx_id = $options['key_trx'].$idorder.'XX';
		foreach ($data2 as $data) {
			if (isset($data['ket'])) {
				if (stristr($data['ket'],$trx_id)) {
					if ((int)str_replace(',','',$data['mutasi']) >= $harga) { 
						$ok = 'Ditemukan dan siap diaktifkan'; 
					} else {
						$kurang = '<p>Pembayaran yang anda lakukan kurang</p>'; 
					}
				}
			}			
		}
		
		if (isset($ok)) {
			aktivasi($idorder,'BCA');
		} else {
			$showtxt .= '
			<h2>Upgrade Gagal</h2>
			<p>Transaksi tidak ditemukan. Apakah anda sudah transfer dan menyertakan kode <strong>'.$trx_id.'</strong> di kolom berita 
			saat transfer? Jika belum, silahkan hubungi admin untuk aktifasi secara manual</p>';
			if (isset($kurang)) { $showtxt .= $kurang; }
		}

	} elseif (isset($_GET['bank']) && $_GET['bank'] == 'mandiri') {
		$data2 = getMandiri();
		$trx_id = $options['key_trx'].$idorder.'XX';		
		foreach ($data2 as $data) {			
			if (isset($data['ket']) && stristr($data['ket'],$trx_id)) {
				$transfer = str_replace('.','',$data['krd']);
				$transfer = str_replace(',00','',$transfer);
				if ($transfer >= $harga) { 
					$ok = 'Ditemukan dan siap diaktifkan'; 
				} else {
					$kurang = '<p>Pembayaran yang anda lakukan kurang</p>'; 
				}
			} 
		}
		
		if (isset($ok)) {
			aktivasi($idorder,'Mandiri');
		} else {
			$showtxt .= '
			<h2>Upgrade Gagal</h2>
			<p>Transaksi tidak ditemukan. Apakah anda sudah transfer dan menyertakan kode <strong>'.$trx_id.'</strong> di kolom berita 
			saat transfer? Jika belum, silahkan hubungi admin untuk aktifasi secara manual</p>';
			if (isset($kurang)) { $showtxt .= $kurang; }
		}
	} elseif (isset($_GET['bank']) && $_GET['bank'] == 'paypal') {
		$harga = number_format($harga/$options['pp_price']);		
		include('paypal.php');
	}

} else {
	if (isset($_GET['idproduk']) && $_GET['idproduk'] == 'premium') {
		$cek = $wpdb->get_var("SELECT `membership` FROM `wp_member` WHERE `idwp`=$user_ID");
		if ($cek == 1) {
			// Buat ordernya
			$cek = $wpdb->get_row("SELECT `id`,`status` FROM `cb_produklain` WHERE `idproduk`=0 AND `idwp`=$user_ID");
			if (!isset($cek->id) || $cek->id == 0) {
				$wpdb->query("INSERT INTO `cb_produklain` VALUES ('',$user_ID,0,0,0,NOW(),0)");
				$orderid = $wpdb->insert_id;
			} else {
				$orderid = $cek->id;
			}
		} else {
			$showtxt .= 'Anda sudah premium member';
		}
	} elseif (isset($_GET['idproduk']) && is_numeric($_GET['idproduk'])) {
		$idproduk = $_GET['idproduk'];
		$cek = $wpdb->get_var("SELECT `id` FROM `cb_produklain` WHERE `idproduk`=$idproduk AND `idwp`=$user_ID");
		if ($cek == NULL) {
			$wpdb->query("INSERT INTO `cb_produklain` VALUES ('',$user_ID,$idproduk,0,0,NOW(),0)");
			$orderid = $wpdb->insert_id;
		} else {
			$orderid = $cek;
		}
	} 

	if (isset($orderid) && is_numeric($orderid)) {
		if ($_GET['idproduk'] == 'premium') {
			$namaproduk = 'Upgrade Premium Member';
			if (isset($options['harga'])) {
				$harga = $options['harga'];
			} else {
				$harga = 0;
			}
		} else {
			$produk = $wpdb->get_row("SELECT `harga`,`nama` FROM `cb_produk` WHERE `id`=$idproduk");
			$namaproduk = $produk->nama;
			$harga = $produk->harga;
		}
		$panjang = strlen($orderid);
		if ($panjang >= 3) {
			$angka = substr($orderid,-3); 
		} else {
			$angka = $orderid;
		}
		$harga = $harga + $angka;

		$showtxt .= '
		<h2>Pembayaran Order</h2>
		<table>
		<tr><td>Nomor Order</td><td>: 
		<strong>'; 

		if (isset($options['key_trx'])) {
			$showtxt .= $options['key_trx'].$orderid.'XX';
		} else {
			$showtxt .= 'ORDER'.$orderid.'XX';
		}

		$showtxt .= '</strong></td></tr>
		<tr><td>Nama Produk</td><td>: '.$namaproduk.'</td></tr>
		<tr><td>Harga</td><td>: '.number_format($harga).'</td></tr>
		</table>';

		if ($options['bca']['uname'] != '' || $options['mandiri']['uname'] != '' || $options['pp_email'] != '' || $options['banklain'] != '') {

			$showtxt .= '
			<select id="payment">
				<option>Pilih Metode Pembayaran</option>';
				if (isset($options['bca']['uname']) && $options['bca']['uname'] != '') { $showtxt .= '<option value="bca">Bank BCA</option>'; }
				if (isset($options['mandiri']['uname']) && $options['mandiri']['uname'] != '') { $showtxt .= '<option value="mandiri">Bank Mandiri</option>'; }
				if (isset($options['pp_email']) && $options['pp_email'] != '') { $showtxt .= '<option value="paypal">Bank PayPal</option>'; }
				if (isset($options['banklain']) && $options['banklain'] != '') { $showtxt .= '<option value="lainnya">Pembayaran Lainnya</option>'; }
			$showtxt .= '</select>';

		} else {
			$showtxt .= 'Maaf, Metode pembayaran belum ditentukan oleh admin';
		}

		$showtxt .= '
		<div id="text"></div>

		<div id="textbca">
			<h3>Instruksi cara Pembayaran di BCA</h3>
			<ol>
			<li>Silahkan transfer sebesar Rp. <strong style="color:#ff0000;">'.number_format($harga).'</strong> ke BCA cabang '.$options['bca']['cabang'].' a/n '.$options['bca']['nama'].' a/c '.$options['bca']['rekening'].'</li>
			<li>Masukkan tulisan ini dalam kolom berita: <strong style="font:600 16px; color:#ff0000;">'.$options['key_trx'].$orderid.'XX'.'</strong></li>
			<li><a href="?page=order&order='.$orderid.'&bank=bca">Klik Disini jika sudah transfer</a></li>
			</ol>
		</div>
		<div id="textmandiri">
			<h3>Instruksi cara Pembayaran di Mandiri</h3>
			<ol>
			<li>Silahkan transfer sebesar Rp. <strong style="color:#ff0000;">'.number_format($harga).'</strong> ke Mandiri cabang '.$options['mandiri']['cabang'].' a/n '.$options['mandiri']['nama'].' a/c '.$options['mandiri']['rekening'].'</li>
			<li>Masukkan tulisan ini dalam kolom berita: <strong style="font:600 16px; color:#ff0000;">'.$options['key_trx'].$orderid.'XX'.'</strong></li>
			<li><a href="?page=order&order='.$orderid.'&bank=mandiri">Klik Disini jika sudah transfer</a></li>
			</ol>
		</div>
		<div id="textpaypal">
		<h3>Instruksi cara Pembayaran di PayPal</h3>
		<ol>
		<li>Harga Produk dalam dollar: $'.number_format($harga/$options['pp_price']).'
		<li><a href="?page=order&order='.$orderid.'&bank=paypal">Klik Disini untuk Membayar dengan PayPal</a></li>
		</ol>
		</div>
		<div id="textlainnya">
		<h3>Instruksi cara Pembayaran Lainnya</h3>
			<ol>
			<li>Abda bisa menukarkan POIN FDC Senilai Rp. 2.000.000 POIN<br/>
			'.nl2br($options['Pembayaran Lainnya']).'</li>
			<li>Pada Link Berikut : <a href="https://fdc-market.com/bayar-upgrade/"> <strong style="color:#ff0000;"><button>Upgrade</button></strong></a> </li>
			<li>Hubungin admin untuk konfirmasi, segera setelah transfer.</li>
			<li>Keanggotaan anda akan diupgrade dalam waktu maksimal 24 jam.</li>
			</ol>
		</div>
		<script>
			var $j = jQuery.noConflict();
			$j(function(){
				$j("#textbca").hide();
				$j("#textmandiri").hide();
				$j("#textpaypal").hide();
				$j("#textlainnya").hide();
			    $j("#payment").change(function () {
					$j("#textbca").hide();
					$j("#textmandiri").hide();
					$j("#textmuamalat").hide();
					$j("#textpaypal").hide();
					$j("#textlainnya").hide();
					var text = $j("#payment").val();
					$j("#text"+text).show();
			    });
			});
		</script>';
	} 
}
?>