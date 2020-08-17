<?php
if ( ! defined( 'IS_IN_SCRIPT' ) ) {
	die();
	exit;
}

?>
<div class="wrap">
<?php
$blogurl = home_url();
if ( is_admin() ) :

	if ( isset( $_POST['bayar'] ) && is_numeric( $_POST['bayar'] ) ) {
		$bayar  = $_POST['bayar'];
		$iduser = $_POST['iduser'];
		$wpdb->query( "UPDATE `wp_member` SET `sisa_voucher`=`sisa_voucher`-" . $bayar . " WHERE `idwp`=" . $iduser );

		// Ambil data member

		$checkupline = $wpdb->get_row( "SELECT * FROM `wp_member` WHERE `idwp` = " . $iduser );
		$id_referral = $checkupline->id_referral;
		$emailmember = $checkupline->email;
		$namamember  = $checkupline->nama;
		$subdomain   = $checkupline->subdomain;
		$username    = $checkupline->username;
		$password    = $checkupline->password;
		$telp        = $checkupline->telp;
		$kota        = $checkupline->kota;
		$provinsi    = $checkupline->provinsi;
		$bank        = $checkupline->bank;
		$rekening    = $checkupline->rekening;
		$ac          = $checkupline->ac;
		$komisi      = $checkupline->sisa_voucher;
		$user_id     = $checkupline->idwp;

		// custom By Fedi tambah poin reward saat bayar bonus
		$point_type = 'reward_poin_fdc';
		$mycred     = mycred( $point_type );

		//Make sure user is not excluded
		if ( ! $mycred->exclude_user( $user_id ) ) {

			// get users balance
			$balance = $mycred->get_users_balance( $user_id );

			// Adjust balance with a log entry
			$r = $mycred->add_creds(
				'poin reward',
				$user_id,
				$bayar,
				'Poin reward Telah Di Tambahkan!'
			);

		}

		// Masukkan laporan keuangan 
		$id_order  = time();
		$transaksi = 'Pembayaran Komisi ' . $checkupline->nama;
		$wpdb->query( "INSERT INTO `cb_laporan` VALUES ('',NOW(),'$transaksi','$bayar',0,0,'wd',0,'$iduser',$id_order)" );

		// Kirim email pemberitahuan ke member yang beruntung ini

		function filteremail($p) {
			global $subdomain, $namamember, $username, $password, $urlreseller;
			global $telp, $kota, $provinsi, $bank, $rekening, $ac, $komisi, $bayar;
			$urlreseller = get_bloginfo( 'url' ) . '/?reg=' . $subdomain;

			$p = str_replace( "{{namamember}}", $namamember, $p );
			$p = str_replace( "{{username}}", $username, $p );
			$p = str_replace( "{{password}}", $password, $p );
			$p = str_replace( "{{urlreseller}}", $urlreseller, $p );
			$p = str_replace( "{{telpmember}}", $telp, $p );
			$p = str_replace( "{{kotamember}}", $kota . ' ' . $provinsi, $p );
			$p = str_replace( "{{namabank}}", $bank, $p );
			$p = str_replace( "{{rekening}}", $rekening, $p );
			$p = str_replace( "{{atasnama}}", $ac, $p );
			$p = str_replace( "{{komisi}}", $bayar, $p );
			$p = str_replace( "{{namaprospek}}", "", $p );
			$p = str_replace( "{{usernameprospek}}", "", $p );
			return $p;


		}

		$body    = filteremail( get_option( 'isi_email_bayar' ) );
		$subject = filteremail( get_option( 'judul_email_bayar' ) );
		$header  = 'From: ' . get_option( 'nama_email' ) . ' <' . get_option( 'alamat_email' ) . '>';

		if ( function_exists( 'wp_mail' ) ) {
			wp_mail( $emailmember, $subject, $body, $header );
		}

		echo '<div id="message2" class="notice notice-success is-dismissible"><p><b>' . $namamember . '</b> Sudah Dibayar</p></div>';
	}
	$options = get_option( "cb_pengaturan" );
	$limit   = $options['limit'];
	$member  = $wpdb->get_results( "SELECT * FROM `wp_member` WHERE `sisa_voucher`>= " . $limit . " ORDER BY `sisa_voucher` DESC" );

	echo'
	<h2>Bayar Member</h2>
	<table class="widefat">
	<thead>
	<tr>
		<th scope="col"  width="35%">Nama Lengkap</th>
		<th scope="col"  width="35%">Data Bank</th>
		<th scope="col"  width="30%">Jumlah Uang</th>
	</tr>
	</thead>
	<tbody>';
	if ( isset( $member ) ) {
		$totalbayar = 0;
		foreach ( $member as $member ) {
			echo '
		<tr>
			<td><a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=cbaf_memberlist&profil=' . $member->idwp . '">' . $member->nama . '</a> <br>
			(' . $member->username . ' | ' . $member->downline_lngsg . ')</td>';
			if ( $member->rekening && $member->bank ) {
				echo '
			<td><b>' . $member->bank . '</b><br>a/n. ' . $member->ac . '<br>' . $member->rekening . '</td>';
			} else {
				echo '
			<td><b>Wesel :</b> ' . $member->alamat . ' ' . $member->kota . ' ' . $member->provinsi . ' ' . $member->kodepos . '</td>';
			}
			echo'
			<td>
			<form action="" method="post">
			Sisa : ' . number_format( $member->sisa_voucher ) . '<br>
			Bayar : <input type="text" name="bayar" size="5" value="' . $member->sisa_voucher . '">
			<input type="hidden" name="iduser" value="' . $member->idwp . '">
			<input type="submit" class="button button-primary" value="Go">
			</form>
			</td>
		</tr>';
			$totalbayar = $totalbayar + $member->sisa_voucher;
		}
	} else {
		echo '<tr><td colspan="3" align="center">Belum ada member yang dibayar</td></tr>';
	}
	echo '
	</tbody>
	</table>
	<p align="center">TOTAL PEMBAYARAN : <b>Rp. ' . number_format( $totalbayar ) . ',-</b></p>
	<p>&nbsp;</p>
</div>';
endif;
