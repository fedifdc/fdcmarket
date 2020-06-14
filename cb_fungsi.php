<?php if (!defined('IS_IN_SCRIPT')) { die();  exit; } 
ob_start();
include('include/menuadmin.php');
include('include/headfoot.php');

function wp_affiliasi() {
}

if (!isset($_SESSION['visit']) || $_SESSION['visit'] == '') {
	global $sponsor; 
	$datasponsor = unserialize(CB_SPONSOR);	
	$id_sponsor = $datasponsor['idwp'];
	if (is_numeric($id_sponsor) && $id_sponsor > 0) {
		$wpdb->query("UPDATE `wp_member` SET `read`=`read`+1 WHERE `idwp`=".$id_sponsor);
		$_SESSION['visit'] = 'ok';
	}
}
	
function cb_install() {
	global $wpdb, $user_ID;
	include ('cb_install.php');
}

function get_urlpendek($url) {
	$options = get_option('cb_pengaturan');
	if (isset($options['shorturl'])) {
		$url = str_replace('[URL]',$url,$options['shorturl']);
		$ch = curl_init();  
		$timeout = 5;  
		curl_setopt($ch,CURLOPT_URL,$url);  
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);  
		$url = curl_exec($ch);  
		curl_close($ch);
		$url = trim($url);
		return $url;
	}
}

function urlpendek($url) {
	echo '<a href="'.get_urlpendek($url).'">'.get_urlpendek($url).'</a>';
}

function getData($url, $agent){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_USERAGENT, $agent);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_ENCODING, "");
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_COOKIEFILE, getcwd() . '/mdr.cok');
	curl_setopt($curl, CURLOPT_COOKIEJAR, getcwd() . '/mdr.cok');
	$data = curl_exec($curl);
	curl_close($curl);
	return $data;
}

function postData($url, $agent, $post, $ref = ''){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL,$url);
	curl_setopt($curl, CURLOPT_USERAGENT, $agent);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
	curl_setopt($curl, CURLOPT_REFERER, $ref);
	curl_setopt($curl, CURLOPT_ENCODING, "");
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER ,1);
	curl_setopt($curl, CURLOPT_COOKIEFILE, getcwd() . '/mdr.cok');
	curl_setopt($curl, CURLOPT_COOKIEJAR, getcwd() . '/mdr.cok');

	$data = curl_exec($curl);
	curl_close ($curl);
	return $data;
}

function GetBetween($content,$start,$end){
    $r = explode($start, $content);
    if (isset($r[1])){
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}

function getMandiri() {
	global $user_ID, $options;	
	$username = $options['mandiri']['uname'];
	$password = $options['mandiri']['paswd'];
	$accid = $options['mandiri']['accid'];
	$url= "https://ib.bankmandiri.co.id/retail/Login.do?action=form&lang=in_ID";
	$data = getData($url, $_SERVER['HTTP_USER_AGENT']);
	$ref= "https://ib.bankmandiri.co.id/retail/Login.do?action=form&lang=in_ID";
	$url= "https://ib.bankmandiri.co.id/retail/Login.do";
	$post = 'action=result&userID='.$username.'&password='.$password;
	$data = postData($url, $_SERVER['HTTP_USER_AGENT'], $post, $ref);

	$now = strtotime('Today');
	list($day1, $month1, $year1) = explode(' ', date('d n Y', $now));
	$minus_t = $now - (24 * 3600 * 3);
	list($day2, $month2, $year2) = explode(' ', date('d n Y', $minus_t));

	$url= "https://ib.bankmandiri.co.id/retail/TrxHistoryInq.do";
	$post = 'action=result&fromAccountID='.$accid.'&searchType=R&fromDay='.$day2.'&fromMonth='.$month2.'&fromYear='.$year2.'&toDay='.$day1.'&toMonth='.$month1.'&toYear='.$year1.'&image.x=0&image.y=0';
	$data = postData($url, $_SERVER['HTTP_USER_AGENT'], $post,'');

	$start = '<!-- Start of Item List -->';
	$end = '<!-- End of Item List -->';
	$data = GetBetween($data,$start,$end);	

	getData('https://ib.bankmandiri.co.id/retail/Logout.do?action=result', $_SERVER['HTTP_USER_AGENT']);
	$items = explode('</tr>',$data);
	$i=0;
	foreach ($items as $item) {
		if ($i >0) {
		$exitem = explode('</td>',$item);
		if (isset($exitem[0])) { $result[$i]['tgl'] = trim(strip_tags($exitem[0],'<br>')); }
		if (isset($exitem[1])) { $result[$i]['ket'] = trim(strip_tags($exitem[1],'<br>')); }
		if (isset($exitem[2])) { $result[$i]['deb'] = trim(strip_tags($exitem[2],'<br>')); }
		if (isset($exitem[3])) { $result[$i]['krd'] = trim(strip_tags($exitem[3],'<br>')); }
		}
		$i++;		
	}
		
	return $result;
}

function getBCA() {
	global $options;
	$username = $options['bca']['uname'];
	$password = $options['bca']['paswd'];
	
	$url = getData('https://ibank.klikbca.com/',$_SERVER['HTTP_USER_AGENT']);
	$curnum = GetBetween($url,'name="value(CurNum)" value="','"');
	$url= "https://ibank.klikbca.com/authentication.do";
	$post = 'value(actions)=login&value(user_id)='.$username.'&value(pswd)='.$password.'&value(user_ip)='.$_SERVER['REMOTE_ADDR'].'&value(Submit)=LOGIN&value(CurNum)='.$curnum;
	$data = postData($url, $_SERVER['HTTP_USER_AGENT'], $post);

	$ref= 'https://ibank.klikbca.com/accountstmt.do?value(actions)=acct_stmt';
	$url= 'https://ibank.klikbca.com/accountstmt.do?value(actions)=acctstmtview';
	$now = strtotime('Today');
	
	list($day1, $month1, $year1) = explode(' ', date('d n Y', $now));
	$minus_t = $now - (24 * 3600 * 3);
	list($day2, $month2, $year2) = explode(' ', date('d n Y', $minus_t));

	$post = 'value(r1)=1&value(D1)=0&value(startDt)='.$day2.'&value(startMt)='.$month2.'&value(startYr)='.$year2.'&value(endDt)='.$day1.'&value(endMt)='.$month1.'&value(endYr)='.$year1.'&value(submit1)=Lihat Mutasi Rekening';
	$data = postData($url, $_SERVER['HTTP_USER_AGENT'], $post, $ref);
	if (stristr($data,'TRANSAKSI ANDA GAGAL')) {
	$return[0][ket] = 'TRANSAKSI ANDA GAGAL';
	} else {
		$start = '<td colspan="2">';
		$end = '</table>';
		$data = GetBetween($data,$start,$end).'</table>';
		$items = explode('</tr>',$data);
		$i=0;
		foreach ($items as $item) {
			$exitem = explode('</td>',$item);
			if (isset($exitem[0])) { $result[$i]['tgl'] = trim(GetBetween($exitem[0],'<font face="verdana" size="1" color="#0000bb">','</font>')); }
			if (isset($exitem[1])) { $result[$i]['ket'] = trim(GetBetween($exitem[1],'<font face="verdana" size="1" color="#0000bb">','</font>')); }
			if (isset($exitem[2])) { $result[$i]['cab'] = trim(GetBetween($exitem[2],'<font face="verdana" size="1" color="#0000bb">','</font>')); }
			if (isset($exitem[3])) { $result[$i]['mutasi'] = trim(GetBetween($exitem[3],'<font face="verdana" size="1" color="#0000bb">','</font>')); }
			if (isset($exitem[4])) { $result[$i]['trx'] = trim(GetBetween($exitem[4],'<font face="verdana" size="1" color="#0000bb">','</font>')); }
			if (isset($exitem[5])) { $result[$i]['saldo'] = trim(GetBetween($exitem[5],'<font face="verdana" size="1" color="#0000bb">','</font>')); }
			$i++;
		}
	}
	getData('https://ibank.klikbca.com/authentication.do?value(actions)=logout', $_SERVER['HTTP_USER_AGENT']);

	return $result;
}

function special($p) {
		$p = htmlentities($p, ENT_COMPAT,'UTF-8');
		$p = str_replace('--','-&minus;',$p);
		return $p;
	}

function txtonly($p) {
	$p = preg_replace("/[^a-zA-Z0-9]+/", "", $p);
	return $p;
}	
function realIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) { $ip=$_SERVER['HTTP_CLIENT_IP']; 
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    } else { $ip=$_SERVER['REMOTE_ADDR']; }
    return $ip;
}

function cb_kontak( $atts, $content = null) {
	global $wpdb, $user_ID;
	global $subdomain, $nama, $username, $password, $urlreseller;
	global $telp, $kota, $provinsi, $bank, $rekening, $ac, $komisi;
	global $namaprospek, $usernameprospek, $bayar, $namamember;
	include("kontak.php");
	return $kontaktxt;
}

add_shortcode('cb_kontak', 'cb_kontak');

function cb_registrasi() {
	global $wpdb, $user_ID;
	global $subdomain, $nama, $email, $username, $password, $urlreseller;
	global $telp, $kota, $provinsi, $bank, $rekening, $ac, $komisi;
	global $namaprospek, $usernameprospek, $bayar, $namamember;
	global $val, $blogurl, $options;
	include("registrasi.php");
	return $showtxt;
}

add_shortcode('cb_registrasi', 'cb_registrasi');

function member($atts) {
	$showtxt = '';
	$a = shortcode_atts( 
		array(
			'data' => 'nama',
			'ganti' => '',
			'text' => 'Chat via WhatsApp',
			'pesan' => 'Mohon info Lengkap'
		), $atts);
	if (isset($_COOKIE['datamember'])) {
		$datamember = unserialize(stripslashes($_COOKIE['datamember']));
		$custommember = unserialize(stripslashes($datamember['homepage']));
		if (is_array($datamember)) {
			if (substr($a['data'],0,6) == 'custom') {
				$showdata = str_replace('custom','',$a['data']);
				if (isset($custommember[$showdata])) {
					$showtxt = $custommember[$showdata];
				} 
			} elseif ($a['data'] == 'whatsapp') {
				if (isset($custommember['whatsapp'])) {
					$wa = $custommember['whatsapp'];
					if ($wa != '') {
						if (substr($wa, 0,1) == '+') {
							$showtxt = '<a href="https://wa.me/'.substr($wa,1).'?text='.urlencode($a['pesan']).'" target="blank">'.$a['text'].'</a>';
						} else {
							$showtxt = '<a href="https://wa.me/62'.$wa.'?text='.urlencode($a['pesan']).'" target="blank">'.$a['text'].'</a>';
						}
					}
				}
			} elseif ($a['data'] == 'foto') {
				if (isset($custommember['pic_profil']) && $custommember['pic_profil'] != '') {
					$showtxt = '<img src="'.$custommember['pic_profil'].'" alt="'.$datamember['nama'].'" class="pic_profil" />';
				}
			} elseif ($a['data'] == 'urlaff') {
				$showtxt = urlaff($datamember['subdomain']);
			} else {
				$showtxt = $datamember[$a['data']];
			}
		}
	} else {
		if ($a['ganti'] != '') {
			$showtxt = $a['ganti'];
		} else {
			$showtxt = '<em>[<a href="'.wp_login_url(get_permalink()).'&reauth=1">Silahkan login dulu</a>]</em>';
		}
	}

	return $showtxt;
}

add_shortcode('member', 'member');

function sponsor($atts) {
	$datasponsor = unserialize(CB_SPONSOR);
	$custom = unserialize($datasponsor['homepage']);
	$showtxt = '';
	$a = shortcode_atts( 
		array(
			'data' => 'nama',
			'text' => 'Chat via WhatsApp',
			'pesan' => 'Mohon info Lengkap'
		), $atts);
		
	if (is_array($datasponsor)) {
		if (substr($a['data'],0,6) == 'custom') {
			$showdata = str_replace('custom','',$a['data']);
			if (isset($custom[$showdata])) {
				$showtxt = $custom[$showdata];
			} 
		} elseif ($a['data'] == 'whatsapp') {
			if (isset($custom['whatsapp'])) {
				$wa = $custom['whatsapp'];
				if ($wa != '') {
					if (substr($wa, 0,1) == '+') {
						$showtxt = '<a href="https://wa.me/'.substr($wa,1).'?text='.urlencode($a['pesan']).'" target="blank">'.$a['text'].'</a>';
					} else {
						$showtxt = '<a href="https://wa.me/62'.$wa.'?text='.urlencode($a['pesan']).'" target="blank">'.$a['text'].'</a>';
					}
				}
			}
		} elseif ($a['data'] == 'foto') {
			if (isset($custom['pic_profil']) && $custom['pic_profil'] != '') {
				$showtxt = '<img src="'.$custom['pic_profil'].'" alt="'.$datasponsor['nama'].'" class="pic_profil" />';
			}
		} elseif ($a['data'] == 'urlaff') {
			$showtxt = urlaff($datasponsor['subdomain']);
		} else {
			$showtxt = $datasponsor[$a['data']];
		}
	} else {
		$showtxt = 'sponsor anda';
	}

	return $showtxt;
}

add_shortcode('sponsor', 'sponsor');

function cb_memberarea() {	
	global $wpdb, $user_ID, $member;
	global $subdomain, $nama, $username, $password, $urlreseller;
	global $telp, $kota, $provinsi, $bank, $rekening, $ac, $komisi;
	global $namaprospek, $usernameprospek, $bayar, $namamember;
	global $val, $blogurl, $options;
	$showtxt = '';
	if (isset($user_ID) && is_numeric($user_ID) && $user_ID > 0) {
		$menuoption = get_option('menuoption');
		if (is_array($menuoption)) {
			$showtxt .= '<p><a href="'.site_url().'/?page_id='.get_the_ID().'&page=home">Home</a>';
			if (isset($menuoption['profil_cek']) && $menuoption['profil_cek'] == 1) { 
				$showtxt .= ' | <a href="'.site_url().'/?page_id='.get_the_ID().'&page=profil">';
				if ($menuoption['profil_label'] != '') { $showtxt .= $menuoption['profil_label']; } else { $showtxt .= 'Profil';}
				$showtxt .= '</a>';
			}
			if (isset($menuoption['laporan_cek']) && $menuoption['laporan_cek'] == 1) { 
				$showtxt .= ' | <a href="'.site_url().'/?page_id='.get_the_ID().'&page=laporan">';
				if ($menuoption['laporan_label'] != '') { $showtxt .= $menuoption['laporan_label']; } else { $showtxt .= 'Laporan';}
				$showtxt .= '</a>';
			}
			if (isset($menuoption['banner_cek']) && $menuoption['banner_cek'] == 1) { 
				$showtxt .= ' | <a href="'.site_url().'/?page_id='.get_the_ID().'&page=promosi">';
				if ($menuoption['banner_label'] != '') { $showtxt .= $menuoption['banner_label']; } else { $showtxt .= 'Banner';}
				$showtxt .= '</a>';
			}
			if (isset($menuoption['klien_cek']) && $menuoption['klien_cek'] == 1) { 
				$showtxt .= ' | <a href="'.site_url().'/?page_id='.get_the_ID().'&page=klien">';
				if ($menuoption['klien_label'] != '') { $showtxt .= $menuoption['klien_label']; } else { $showtxt .= 'Klien';}
				$showtxt .= '</a>';
			}
			if (isset($menuoption['jaringan_cek']) && $menuoption['jaringan_cek'] == 1) { 
				$showtxt .= ' | <a href="'.site_url().'/?page_id='.get_the_ID().'&page=jaringan">';
				if ($menuoption['jaringan_label'] != '') { $showtxt .= $menuoption['jaringan_label']; } else { $showtxt .= 'Jaringan';}
				$showtxt .= '</a>';
			}
			if (isset($menuoption['download_cek']) && $menuoption['download_cek'] == 1) { 
				$showtxt .= ' | <a href="'.site_url().'/?page_id='.get_the_ID().'&page=download">';
				if ($menuoption['download_label'] != '') { $showtxt .= $menuoption['download_label']; } else { $showtxt .= 'Download';}
				$showtxt .= '</a>';
			}
			if (isset($menuoption['upgrade_cek']) && $menuoption['upgrade_cek'] == 1) { 
				if ($wpdb->get_var("SELECT `membership` FROM `wp_member` WHERE idwp = ".$user_ID) == 1) {
					$showtxt .= ' | <a href="'.site_url().'/?page_id='.get_the_ID().'&page=order&idproduk=premium">';
					if ($menuoption['upgrade_label'] != '') { $showtxt .= $menuoption['upgrade_label']; } else { $showtxt .= 'Upgrade';}
					$showtxt .= '</a>';
				}
			}
			if (isset($menuoption['logout_cek']) && $menuoption['logout_cek'] == 1) { 
				$showtxt .= ' | <a href="'.wp_logout_url(site_url()).'">';
				if ($menuoption['logout_label'] != '') { $showtxt .= $menuoption['logout_label']; } else { $showtxt .= 'Logout';}
				$showtxt .= '</a>';
			}		

		} else {
			$showtxt .= '<p><a href="'.site_url().'/?page_id='.get_the_ID().'&page=home">Home</a> | 
			<a href="'.site_url().'/?page_id='.get_the_ID().'&page=profil">Profil</a> | 
			<a href="'.site_url().'/?page_id='.get_the_ID().'&page=laporan">Laporan</a> | 
			<a href="'.site_url().'/?page_id='.get_the_ID().'&page=promosi">Banner</a> | 
			<a href="'.site_url().'/?page_id='.get_the_ID().'&page=klien">Klien</a> | 
			<a href="'.site_url().'/?page_id='.get_the_ID().'&page=jaringan">Jaringan</a> | 
			<a href="'.site_url().'/?page_id='.get_the_ID().'&page=download">Download</a>';
			if ($wpdb->get_var("SELECT `membership` FROM `wp_member` WHERE idwp = ".$user_ID) == 1) {
			 $showtxt .= ' | <a href="'.site_url().'/?page_id='.get_the_ID().'&page=order&idproduk=premium">Upgrade</a>'; 
			}
			$showtxt .= '
			| <a href="'.wp_logout_url(site_url()).'">Logout</a></p>';
		}

		$memberpage = '';
		if (isset($_GET['page'])) { $memberpage = $_GET['page']; }
		switch ($memberpage) {
		case 'profil' : include('memberprofil.php'); break;
		case 'laporan' : include('memberlaporan.php'); break;
		case 'promosi' : include('memberpromosi.php'); break;
		case 'klien' : include('memberklien.php'); break;
		case 'jaringan' : include('memberjaringan.php'); break;
		case 'download' : include('memberdownload.php'); break;	
		case 'order' : include('memberorder.php'); break;
		default : include("memberarea.php");
		}
	} else {
		$refer = $_SERVER['REQUEST_URI'];
		header("Location: ".wp_login_url($refer));
	}

	return $showtxt;
}

add_shortcode('cb_memberarea', 'cb_memberarea');

function pagemember($atts) {
	global $user_ID, $wpdb;
	$showtxt = '';
	$a = shortcode_atts( 
		array(
			'data' => 'home'
		), $atts);
	switch ($a['data']) {
		case 'profil' : include('memberprofil.php'); break;
		case 'laporan' : include('memberlaporan.php'); break;
		case 'promosi' : include('memberpromosi.php'); break;
		case 'klien' : include('memberklien.php'); break;
		case 'jaringan' : include('memberjaringan.php'); break;
		case 'download' : include('memberdownload.php'); break;	
		case 'order' : include('memberorder.php'); break;
		default : include("memberarea.php");
	}
	return $showtxt;
}

add_shortcode('pagemember', 'pagemember');

function gantipost($content) {
	global $wpdb, $user_ID;
	global $subdomain, $nama, $username, $password, $urlreseller;
	global $telp, $kota, $provinsi, $bank, $rekening, $ac, $komisi;
	global $namaprospek, $usernameprospek, $bayar, $namamember;
	$options = get_option('cb_pengaturan');
	if ($user_ID) {
		$membership = $wpdb->get_var("SELECT `membership` FROM `wp_member` WHERE `idwp`='$user_ID'");
		if ($membership == 1) {
			if (strpos($content,'[premium]')) {
				$content = substr($content,0,strpos($content,'[premium]'));	
				$content .= '<p>Kelanjutan artikel ini hanya bisa dibaca oleh <b>Premium Member</b>, Silahkan Upgrade dulu</a></p>';
			}
			$content = str_replace('[freemember]','',$content);
		} else {
			$content = str_replace('[freemember]','',$content);
			$content = str_replace('[premium]','',$content);
		}
	} else {
		if (strpos($content,'[freemember]')) {
			$content = substr($content,0,strpos($content,'[freemember]'));
			$content .= '<p>Kelanjutan artikel ini hanya bisa dibaca oleh Member, <a href="'.wp_login_url(get_permalink()).'&reauth=1">silahkan login disini dulu</a> atau <a href="'.site_url().'/?page_id='.$options['registrasi'].'">Registrasi di sini</a></p>';
		} elseif (strpos($content,'[premium]')) {
			$content = substr($content,0,strpos($content,'[premium]'));
			$content .= '<p>Kelanjutan artikel ini hanya bisa dibaca oleh <b>Premium Member</b>, <a href="'.wp_login_url(get_permalink()).'&reauth=1">silahkan login disini dulu</a> atau <a href="'.site_url().'/?page_id='.$options['registrasi'].'">Registrasi di sini</a></p>';
		}
	}	
	return $content;
}
add_filter('the_content','gantipost',10);
add_filter('authenticate', 'check_login', 10, 3);
function check_login($user, $username, $password) {
	global $wpdb;
	$cokuser = $username;
    if (isset($username) && $username != '') {
        //$user_data = $user->data;

        if (!username_exists( $username )) {
          $valid = $wpdb->get_row("SELECT * FROM `wp_member` WHERE `username` =  '".$username."' AND `password`='".$password."'",ARRAY_A);
		  if (isset($valid['username'])) {				
			$id_sponsor = $valid['id_referral'];
			if ($id_sponsor == 0) { $id_sponsor = $valid['idwp']; }
			$nama = $valid['nama'];
			$email = $valid['email'];
			$username = $valid['username'];
			$password = $valid['password'];
			$subdomain = $valid['subdomain'];
			$affiliasi = urlaff($valid['subdomain']);
			
			// Buat akun di WordPress
			$daftar = wp_create_user($username, $password, $email);
			$passdb = md5($password);
			$wpdb->query("UPDATE `wp_member` SET `membership` = 1, `password`='".$passdb."', `idwp`='".$daftar."', `tgl_daftar` = NOW() WHERE `username` = '".$username."'");
			
			$komisi = get_option('komisi');
			$options = get_option('cb_pengaturan');
			$freePPL = $komisi['pplfree'];
			$premiumPPL = $komisi['pplpremium'];
			
			$ip = realIP();
			$checkip = $wpdb->get_var("SELECT COUNT(*) FROM `wp_member` WHERE `ip`='".$ip."'");
			if ($checkip >= 2) {
				$freePPL = 0;
				$premiumPPL = 0;
				}			
			
			// Ambil info status keanggotaan sponsor
			$status = $wpdb->get_var("SELECT `membership` FROM `wp_member` WHERE `idwp` = ".$id_sponsor);		
			if ($status == 1) { $voucher = $freePPL; } elseif ($status >=2) { $voucher = $premiumPPL; } else { $voucher = 0;}
			
			// Tambah Komisi Sponsor
			if (isset($voucher) && $voucher > 0) {
				$wpdb->query("UPDATE `wp_member` SET `sisa_voucher` = `sisa_voucher`+$voucher, `jml_voucher`=`jml_voucher`+$voucher  WHERE `idwp` = ".$id_sponsor);
				$wpdb->query("INSERT INTO `cb_laporan` (`tanggal`,`transaksi`,`debet`,`kredit`,`komisi`,`keterangan`,`id_user`,`id_sponsor`,`id_order`) VALUES (NOW(), 'Komisi PPL oleh ".$nama."',0,0,".$voucher.",'ppl',".$daftar.",".$id_sponsor.",0)");
			}
			
			$datasponsor = $wpdb->get_row("SELECT * FROM `wp_member` WHERE `idwp`='".$id_sponsor."'",ARRAY_A);
			$cokmember = serialize($valid);
			setcookie("datamember",$cokmember,strtotime('+30 days'),'/');
			setcookie("idsponsor",$datasponsor['idwp'],strtotime('+30 days'),'/');
			
			return $daftar;
		  } else {
			return null;
		  }
        } else {
			$datamember = $wpdb->get_row("SELECT * FROM `wp_member` WHERE `username`='".$username."'",ARRAY_A);
			if ($datamember['id_referral'] == 0) { $id_sponsor = $datamember['idwp']; } else { $id_sponsor = $datamember['id_referral']; }
			$datasponsor = $wpdb->get_row("SELECT * FROM `wp_member` WHERE `idwp`='".$id_sponsor."'",ARRAY_A);
			$cokmember = serialize($datamember);
			setcookie("datamember",$cokmember,strtotime('+30 days'),'/');
			setcookie("idsponsor",$datasponsor['idwp'],strtotime('+30 days'),'/');
			return $user;
        }
    }	
	return $user;
}

function cb_logout() {
	setcookie("datamember",'',strtotime('+30 days'),'/');
}

add_action('wp_logout', 'cb_logout');

function cb_stats_widget($args) {
	extract($args);
	$data = get_option('wp_affiliasi_widget');
	echo $before_widget;
	echo $before_title.$data['judulstats'].$after_title;
	echo '<ul>';
	if ($data['stats'][0] == 'free') { echo '<li>Free Member: '.get_jml_member($data['stats'][0]).'</li>'; }
	if ($data['stats'][1] == 'premium') { echo '<li>Premium Member: '.get_jml_member($data['stats'][1]).'</li>'; }
	if ($data['stats'][2] == 'total') { echo '<li>Total Member: '.get_jml_member($data['stats'][2]).'</li>'; }
	echo '</ul>';
	echo $after_widget;
}

function cb_stats_control() {
	$data = get_option('wp_affiliasi_widget');
	?>
	<p><label>Title:</label><br/>
	<input name="judulstats" type="text" value="<?php echo $data['judulstats']; ?>" size="30"/></p>
	<p><label>Tampilkan Statistik:</label><br/>
	<input name="stats[0]" type="checkbox" value="free" <?php if ($data['stats'][0] == 'free') { echo 'checked="yes"'; }?>/> Free Member<br/>
	<input name="stats[1]" type="checkbox" value="premium" <?php if ($data['stats'][1] == 'premium') { echo 'checked="yes"'; }?>/> Premium Member<br/>
	<input name="stats[2]" type="checkbox" value="total" <?php if ($data['stats'][2] == 'total') { echo 'checked="yes"'; }?>/> Total Jumlah Member <br/>
	</p>
	<?php
	if (isset($_POST['judulstats'])){
	    $data['judulstats'] = attribute_escape($_POST['judulstats']);
	    $data['stats'] = ($_POST['stats']);
	    update_option('wp_affiliasi_widget', $data);
	}
}

function cb_list_widget($args) {
	extract($args);
	$data = get_option('wp_affiliasi_widget');
	if (isset($data['marqueecheck']) && $data['marqueecheck'] == 1) {
		$marque = ' class="marquee ver" data-direction="up" data-duration="4000" data-pauseOnHover="true"';
	} else {
		$marque = '';
	}
	if (isset($data['premiumcheck']) && $data['premiumcheck'] == 1) {
		echo $before_widget;
		echo $before_title.$data['judulpremium'].$after_title;
		echo '<div'.$marque.'>';
		echo '<ul>';
		if (isset($data['jmlpremium']) && $data['jmlpremium'] > 0) { $number = $data['jmlpremium']; } else { $number = 5; }
		cb_list(2,'<li>nama</li>',$number);
		echo '</ul>';
		echo '</div>';
		echo $after_widget;
	}
	if (isset($data['freecheck']) && $data['freecheck'] == 1) {
		echo $before_widget;
		echo $before_title.$data['judulfree'].$after_title;
		echo '<div'.$marque.'>';
		echo '<ul>';
		if (isset($data['jmlfree']) && $data['jmlfree'] > 0) { $number = $data['jmlfree']; } else { $number = 5; }
		cb_list(1,'<li>nama</li>',$data['jmlfree']);
		echo '</ul>';
		echo '</div>';
		echo $after_widget;
	}
	if (isset($data['allcheck']) && $data['allcheck'] == 1) {
		echo $before_widget;
		echo $before_title.$data['judulall'].$after_title;
		echo '<div'.$marque.'>';
		echo '<ul>';
		if (isset($data['jmlall']) && $data['jmlall'] > 0) { $number = $data['jmlall']; } else { $number = 5; }
		cb_list('all','<li>nama</li>',$data['jmlall']);
		echo '</ul>';
		echo '</div>';
		echo $after_widget;
	}
	
}

function cb_list_control() {
	$data = get_option('wp_affiliasi_widget');
	echo '
	<p><input type="checkbox" value="1" name="marqueecheck"';
	if (isset($data['marqueecheck']) && $data['marqueecheck'] == 1) { echo 'checked="yes"'; }
	echo '/> Marquee</p>
	<p>List Member Premium<br/>
	<input type="checkbox" value="1" name="premiumcheck"';
	if (isset($data['premiumcheck']) && $data['premiumcheck'] == 1) { echo 'checked="yes"'; }
	echo '/>
	<input type="text" name="judulpremium" placeholder="Judul List" value="'.$data['judulpremium'].'" size="22"/>
	<input type="number" name="jmlpremium" value="'.$data['jmlpremium'].'" style="width:60px"/></p>';
	echo '<p>List Member Free<br/>
	<input type="checkbox" value="1" name="freecheck"';
	if (isset($data['freecheck']) && $data['freecheck'] == 1) { echo 'checked="yes"'; }
	echo '/>
	<input type="text" name="judulfree" placeholder="Judul List" value="'.$data['judulfree'].'" size="22"/>
	<input type="number" name="jmlfree" value="'.$data['jmlfree'].'" style="width:60px"/></p>';
	echo '<p>List Semua Member<br/>
	<input type="checkbox" value="1" name="allcheck"';
	if (isset($data['allcheck']) && $data['allcheck'] == 1) { echo 'checked="yes"'; }
	echo '/>
	<input type="text" name="judulall" placeholder="Judul List" value="'.$data['judulall'].'" size="22"/>
	<input type="number" name="jmlall" value="'.$data['jmlall'].'" style="width:60px"/></p>';
	
	if (isset($_POST['judulpremium'])){
	    $data['premiumcheck'] = attribute_escape($_POST['premiumcheck']);
		$data['freecheck'] = attribute_escape($_POST['freecheck']);
		$data['allcheck'] = attribute_escape($_POST['allcheck']);
		$data['judulpremium'] = attribute_escape($_POST['judulpremium']);
		$data['judulfree'] = attribute_escape($_POST['judulfree']);
		$data['judulall'] = attribute_escape($_POST['judulall']);
		$data['jmlpremium'] = attribute_escape($_POST['jmlpremium']);
		$data['jmlfree'] = attribute_escape($_POST['jmlfree']);
		$data['jmlall'] = attribute_escape($_POST['jmlall']);
		$data['marqueecheck'] = attribute_escape($_POST['marqueecheck']);
	    update_option('wp_affiliasi_widget', $data);
	}
	
}

function cb_() {
	$options = get_option('cb_pengaturan');
	$url = 'https://'.'lisensi.'.'cafe'.'bisnis.com/cek.php?'.'c='.$options['lisensi'];
	$cbcek = getData($url,$_SERVER['HTTP_USER_AGENT']);
	if ($cbcek == 'error') {
		unset($options['lisensi']);
		update_option('cb_pengaturan', $options);
	}

}

function cb_aff_init() {
	$ops_stats = array('classname' => 'wp_aff_stats', 'description' => "Menampilkan statistik jumlah member", 'number' => 5 );
	wp_register_sidebar_widget('wp_aff','Statistik Member', 'cb_stats_widget',$ops_stats); 
	wp_register_widget_control('wp_aff','Statistik Member', 'cb_stats_control');
	wp_register_sidebar_widget('cb_list','List Member Terbaru', 'cb_list_widget');
	wp_register_widget_control('cb_list','List Member Terbaru', 'cb_list_control');
}
add_action("plugins_loaded", "cb_aff_init");

function get_cb_datasponsor($field) {
	global $id_sponsor, $wpdb;
	if (isset($_COOKIE['sponsor']) && !$_GET['reg']) {
		$id_sponsor = $_COOKIE['sponsor'];
	} 
	
	$cb_tampil = $wpdb->get_var("SELECT `$field` FROM `wp_member` WHERE `idwp`='$id_sponsor'");
	return $cb_tampil;
}

function cb_datasponsor($tampillist) {
	$datasponsor = unserialize(CB_SPONSOR);
	$datalain = unserialize($datasponsor['homepage']);
	$tampillist = str_replace('idmlm',$datasponsor['id_tianshi'],$tampillist);
	$tampillist = str_replace('nama',$datasponsor['nama'],$tampillist);
	$tampillist = str_replace('alamat',$datasponsor['alamat'],$tampillist);
	$tampillist = str_replace('kota',$datasponsor['kota'],$tampillist);
	$tampillist = str_replace('provinsi',$datasponsor['provinsi'],$tampillist);
	$tampillist = str_replace('kodepos',$datasponsor['kodepos'],$tampillist);
	$tampillist = str_replace('telp',$datasponsor['telp'],$tampillist);
	$tampillist = str_replace('ac',$datasponsor['ac'],$tampillist);
	$tampillist = str_replace('bank',$datasponsor['bank'],$tampillist);
	$tampillist = str_replace('rekening',$datasponsor['rekening'],$tampillist);
	$tampillist = str_replace('email',$datasponsor['email'],$tampillist);
	$urlaffiliasi = urlaff($datasponsor['subdomain']);
	$tampillist = str_replace('urlaffiliasi',$urlaffiliasi,$tampillist);
	if ($datasponsor['homepage'] == '') {
		$avatar = '<img src="http://www.gravatar.com/avatar/'.md5(strtolower($datasponsor['email'])).'/?r=R&s=200" alt="'.$datasponsor['nama'].'" style="width:175px; height:175px; float:left; margin-right:5px;">';
	} else {
		$avatar = '<img src="'.$datalain['pic_profil'].'" alt="'.$datasponsor['nama'].'" style="width:175px; height:175px; float:left; margin-right:5px;">';
	}

	$tampillist = str_replace('avatar',$avatar,$tampillist);
	echo $tampillist;	
}

function cb_list($status=2,$format='<li>nama (kota)</li>',$number=5) {
	global $wpdb;
	if ($number == '') { $number = 5; }
	if ($status == 'all') {
		$listprem = $wpdb->get_results("SELECT * FROM `wp_member` ORDER BY `tgl_daftar` DESC LIMIT 0,".$number);
	} else {
		$listprem = $wpdb->get_results("SELECT * FROM `wp_member` WHERE `membership` = ".$status." ORDER BY `tgl_daftar` DESC LIMIT 0,".$number);
	}
	foreach ($listprem as $listprem) {
		$tampillist = str_replace('nama',$listprem->nama,$format);
		$tampillist = str_replace('alamat',$listprem->alamat,$tampillist);
		$tampillist = str_replace('kota',$listprem->kota,$tampillist);
		$tampillist = str_replace('provinsi',$listprem->provinsi,$tampillist);
		$tampillist = str_replace('kodepos',$listprem->kodepos,$tampillist);
		$tampillist = str_replace('telp',$listprem->telp,$tampillist);
		$tampillist = str_replace('ac',$listprem->ac,$tampillist);
		$tampillist = str_replace('bank',$listprem->bank,$tampillist);
		$tampillist = str_replace('rekening',$listprem->rekening,$tampillist);
		$tampillist = str_replace('email',$listprem->email,$tampillist);
		$urlaffiliasi = urlaff($listprem->subdomain);
		$tampillist = str_replace('urlaffiliasi',$urlaffiliasi,$tampillist);		
		echo $tampillist;
	}
}

function get_jml_member($status) {
	global $wpdb;
	if ($status == 'free') {
		$membership = 1;
	} elseif ($status == 'premium') {
		$membership = 2;
	} else {
		$membership = 1;
	}
	if ($status == 'total') {
	$cb_jml_member = $wpdb->get_var("SELECT COUNT(*) FROM `wp_member` ORDER BY `tgl_daftar` DESC");
	return $cb_jml_member;
	} else {
	$cb_jml_member = $wpdb->get_var("SELECT COUNT(*) FROM `wp_member` WHERE `membership`=$membership ORDER BY `tgl_daftar` DESC");
	return $cb_jml_member;
	}
}

function cb_jml_member($status) {
	echo get_jml_member($status);
}

function aktivasi($id2up,$thebank="") {
	global $wpdb, $user_ID, $user_identity, $sponsorkita;
	global $subdomain, $nama, $username, $password, $urlreseller;
	global $telp, $kota, $provinsi, $bank, $rekening, $ac, $komisi;
	global $namaprospek, $usernameprospek, $status, $blogurl;	
	include('aktivasi.php');
}

class classsponsor extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array( 
			'classname' => 'classsponsor',
			'description' => 'Menampilkan Data Sponsor',
		);
		parent::__construct( 'classsponsor', 'Data Sponsor', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$datasponsor = unserialize(CB_SPONSOR);
		extract($args, EXTR_SKIP);
		/*
		if (isset($_COOKIE['sponsor']) && !isset($_GET['reg'])) {
			$datasponsor = unserialize(stripslashes($_COOKIE['sponsor']));
		} else {			
			$datasponsor = unserialize(stripslashes($sponsor));
		}
		*/

		
		if (is_array($datasponsor) && is_array($instance['widgetsponsor'])) {
		$custom = unserialize($datasponsor['homepage']);	
		if (isset($custom[0])) {
			$ym = $custom[0];
		}
		echo $before_widget;
		echo $before_title.$instance['title'].$after_title;
		
		foreach ($instance['widgetsponsor'] as $widget) {
			if (substr($widget,0,6) == 'custom') {
				$cus = str_replace('custom[','',$widget);
				$cus = str_replace(']','',$cus);
				if (isset($custom['whatsapp']) && $cus == 'customwhatsapp') {
					echo '<a href="https://wa.me/62'.$custom['whatsapp'].'">Chat via WhatsApp</a><br/>';
				} elseif (isset($custom[$cus])) {
					echo $custom[$cus].'<br/>';
				}

			} elseif ($widget == 'ym') {
				if (isset($ym)) {
				echo '<a href="ymsgr:sendIM?'.$ym.'"><img border="0" src="http://opi.yahoo.com/online?u='.$ym.'&m=g&t=2&l=us" width="125" height="25" alt="Chat dengan Sponsor" /></a><br/>';
				}
			} elseif ($widget == 'avatar') {
				if ($custom['pic_profil'] == '') {
					echo '<img src="http://www.gravatar.com/avatar/'.md5(strtolower($datasponsor['email'])).'/?r=R&s=100" alt="'.$datasponsor['nama'].'" style="width:175px; height:175px; margin-bottom:10px"><br/>';
				} else {
					echo '<img src="'.$custom['pic_profil'].'" alt="'.$datasponsor['nama'].'" style="width:175px; height:175px; margin-bottom:10px"><br/>';
				}
			} elseif ($widget == 'subdomain') {
				if (get_option('affsub') == 1) {
					echo '<a href="http://'.$datasponsor['subdomain'].'.'.get_option('domain').'">http://'.$datasponsor['subdomain'].'.'.get_option('domain').'</a><br/>';
				} else {
					echo '<a href="'.site_url().'/?reg='.$datasponsor['subdomain'].'">'.site_url().'/?reg='.$datasponsor['subdomain'].'</a><br/>';
				}
			} elseif ($widget == 'password') {
				//diem aja
			} else {
				echo '<span class="sponsor'.$widget.'">'.$datasponsor[$widget].'</span><br/>';
			}
		}
		echo $after_widget;
		} else {
			echo 'DATA SPONSOR BLM ADA';
		}
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		//$data = get_option('wp_affiliasi_widget');
		$default = 	array( 'title' => __('Data Sponsor') );
		$instance = wp_parse_args( (array) $instance, $default );
		$w = 1;
		$c = 1;
		echo '<p><label>Title:</label><br/>
		<input name="'.$this->get_field_name('title').'" type="text" value="'. esc_attr( $instance['title'] ).'" size="30"/></p>
		<p><label>Data yang ditampilkan</label><br/>
		<input name="'.$this->get_field_name('widgetsponsor').'[0]" type="checkbox" value="avatar"'; 
			if (isset($instance['widgetsponsor'][0])) { echo 'checked="checked"'; }
			echo '/> Gravatar<br/>';
		$aturform = get_option('aturform');
		$form = unserialize($aturform);
		if (is_array($form)) {
			foreach ($form as $form) {
			
			if ($form['profil'] == 1) {
				if (empty($form['label'])) {
					switch ($form['field']) {
						case 'nama' : $label = 'Nama Lengkap'; $required = 'required'; break;
						case 'id_tianshi' : $label = 'ID MLM'; break;
						case 'email' : $label = 'Email'; break;
						case 'ktp' : $label = 'No. KTP'; break;
						case 'tgl_lahir' : $label = 'Tanggal Lahir'; break;
						case 'alamat' : $label = 'Alamat'; break;
						case 'kota' : $label = 'Kota'; break;
						case 'provinsi' : $label = 'Provinsi'; break;
						case 'kodepos' : $label = 'Kodepos'; break;
						case 'telp' : $label = 'No. Telp / HP'; break;
						case 'ktp_istri' : $label = 'No. KTP Pasangan'; break;
						case 'nama_istri' : $label = 'Nama Pasangan'; break;
						case 'tgl_lahir_istri' : $label = 'Tgl Lahir Pasangan'; break;
						case 'ac' : $label = 'Atas Nama'; break;
						case 'bank' : $label = 'Nama Bank'; break;
						case 'rekening' : $label = 'No. Rekening'; break;
						case 'kelamin' : $label = 'Jenis Kelamin'; break;
						case 'username' : $label = 'Username'; break;
						case 'subdomain' : $label = 'URL Affiliasi'; break;
						case 'ym' : $label = 'Yahoo Messenger'; break;						
					}
				} else {
					$label = $form['label'];
				}
				if ($form['field'] != 'keterangan' && $form['field'] != 'password') {
					if ($form['field'] == 'custom') {
						echo '<input name="'.$this->get_field_name('widgetsponsor').'['.$w.']" type="checkbox" value="custom['.$c.']"'; 
						if (isset($instance['widgetsponsor'][$w])) { echo 'checked="checked"'; }
						echo '/> '.$label.'<br/>';
						$c++;
					} else {
						echo '<input name="'.$this->get_field_name('widgetsponsor').'['.$w.']" type="checkbox" value="'.$form['field'].'"'; 
						if (isset($instance['widgetsponsor'][$w])) { echo 'checked="checked"'; }
						echo '/> '.$label.'<br/>';
					}
					$w++;
				}
			}
			}
		}
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['widgetsponsor'] = $new_instance['widgetsponsor'];
		return $instance;
	}
}

/* register widget when loading the WP core */
add_action('widgets_init', 'widget_sponsor');

function widget_sponsor(){
	register_widget('classsponsor');
}

add_action( 'woocommerce_checkout_update_order_meta', 'cbaff_addsponsor' );

function cbaff_addsponsor( $order_id ) {
	global $wpdb, $user_ID;
	if (isset($user_ID) && $user_ID > 0) {
		$datasponsor = $wpdb->get_var("SELECT `id_referral` FROM `wp_member` WHERE `idwp`=".$user_ID);
	} else {
		$sponsor = unserialize(CB_SPONSOR);
		$datasponsor = $sponsor['idwp'];
	}

	if (isset($datasponsor) && is_numeric($datasponsor)) {
		update_post_meta($order_id, 'Sponsor ID', $datasponsor);
	}	
}

//add_action('woocommerce_order_status_completed', 'cbaff_processorder');

function cbaff_processorder($order_id) {
	global $wpdb, $user_ID;
	$order = new WC_Order( $order_id );
	$kredit = $order->get_total() - $order->get_total_shipping();
	$id_user = $order->customer_user;
    $id_sponsor = get_post_meta($order_id,'Sponsor ID',true);

    if (!function_exists(filteremail)) {
	    function filteremail($p) {
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
    
    $status = $wpdb->get_row("SELECT * FROM `wp_member` WHERE `idwp`=$id_sponsor");
    
	$custom = unserialize($status->homepage);
	if (!isset($custom['uplines'])) {
		$custom['uplines'] = cbaff_uplines($status->id_referral);
		$customdb = serialize($custom);
		$wpdb->query("UPDATE `wp_member` SET `homepage`='$customdb' WHERE `idwp`=$id_sponsor");
	}

	if ($custom['uplines'] != 0) {
		$iduplines = $id_sponsor.','.$custom['uplines'];
		$uplines = $wpdb->get_results("SELECT * FROM `wp_member` WHERE `idwp` IN ($iduplines) ORDER BY FIELD(`idwp`,$iduplines)");
	} else {
		$uplines = $wpdb->get_results("SELECT * FROM `wp_member` WHERE `idwp`=$id_sponsor");
	}
	$komisi = get_option('komisi');
	$i = 0;

	foreach ($uplines as $upline) {
		if ($upline->idwp != 0) {
			$jmlkomisi = 0;
		    $id_referral = '';
			if ($komisi['pps'][$i]['free']==0 && $upline->membership==1) {
				// Lewati
			} else {
				if ($upline->membership == 2) {
					if (isset($komisi['pps'][$i]['woopremium']) && $komisi['pps'][$i]['woopremium'] > 0) {
						$jmlkomisi = ($komisi['pps'][$i]['woopremium']/100)*$kredit;
					} else {
						$jmlkomisi = ($komisi['pps'][$i]['premium']/100)*$kredit;
					}
				} else {
					if (isset($komisi['pps'][$i]['woofree']) && $komisi['pps'][$i]['woofree'] > 0) {
						$jmlkomisi = ($komisi['pps'][$i]['woofree']/100)*$kredit;
					} else {
						$jmlkomisi = ($komisi['pps'][$i]['free']/100)*$kredit;
					}
				}

				$id_referral = $upline->idwp;

				if ($i==0) {
					$wpdb->query("UPDATE `wp_member` SET `downline_lngsg`=`downline_lngsg`+1,`jml_downline`=`jml_downline`+1,`jml_voucher`=`jml_voucher`+'$jmlkomisi', `sisa_voucher`=`sisa_voucher`+'$jmlkomisi' WHERE `idwp` = '$id_referral'");

					$upemail = $upline->email;
					$body = filteremail(get_option('isi_email_sale'));
					$subject = filteremail(get_option('judul_email_sale'));
					$header = 'From: '.get_option('nama_email').' <'.get_option('alamat_email').'>';	

					if (function_exists('wp_mail')) {
					wp_mail($upemail, $subject, $body, $header);
					}
				} else {
					$wpdb->query("UPDATE `wp_member` SET `jml_downline`=`jml_downline`+1,`jml_voucher`=`jml_voucher`+'$jmlkomisi', `sisa_voucher`=`sisa_voucher`+'$jmlkomisi' WHERE `idwp` = '$id_referral'");
				}

				if ($jmlkomisi > 0) {
					$transaksi = 'Penjualan Produk Order No. '.$order_id.' Level '.($i+1);
					$wpdb->query("INSERT INTO `cb_laporan` (`tanggal`,`transaksi`,`kredit`,`komisi`,`keterangan`,`id_user`,`id_sponsor`,`id_order`) VALUES (NOW(),'$transaksi','$kredit','$jmlkomisi','woo','$id_user','$id_referral','$order_id')");
				}
				$i++;
			}
		}
	}

}

add_action('user_register','cbaff_adduser');

function cbaff_adduser($user_id) {
	global $wpdb, $sponsor;
	$userdata = get_userdata( $user_id );
	$username = $userdata->user_login;
	$cekmember = $wpdb->get_var("SELECT `id_user` FROM `wp_member` WHERE `username` LIKE '".$username."'");

	if ($cekmember == NULL && !isset($_POST['nama'])) {
		$id_referral = $_COOKIE['idsponsor'];
		$lainlain['uplines'] = cbaff_uplines($id_referral);
		if (isset($lainlain)) {
			$homepage = serialize($lainlain);
		}
		$nama = $alamat = $kota = $provinsi = $telp = '';
		if (isset($_POST['billing_first_name'])) { $nama = sanitize_text_field($_POST['billing_first_name']); }
		if (isset($_POST['billing_last_name'])) { $nama .= ' '.sanitize_text_field($_POST['billing_last_name']); }
		if (isset($_POST['billing_address_1'])) { $alamat = sanitize_text_field($_POST['billing_address_1']); }
		if (isset($_POST['billing_address_2'])) { $alamat .= ' '.sanitize_text_field($_POST['billing_address_2']); }
		if (isset($_POST['billing_city'])) { $kota = sanitize_text_field($_POST['billing_city']); }
		if (isset($_POST['billing_state'])) { $provinsi = sanitize_text_field($_POST['billing_state']); }
		if (isset($_POST['billing_phone'])) { $telp = sanitize_text_field($_POST['billing_phone']); }
		$ip = $_SERVER['REMOTE_ADDR'];
		$email = $userdata->user_email;
		if (!isset($nama)) {
			if (isset($userdata->first_name)) {
				$nama = $userdata->first_name.' '.$userdata->last_name;
			} else {
				$nama = $username;
			}
		}

		$wpdb->query("INSERT INTO `wp_member` 
			   (`idwp`,`id_referral`,`nama`,`alamat`,`kota`,`provinsi`,`telp`,`tgl_daftar`,`username`,`email`,`subdomain`,`homepage`,`membership`,`ip`) 
				VALUES (".$user_id.",".$id_referral.",'".$nama."','".$alamat."','".$kota."','".$provinsi."','".$telp."',NOW(),'".$username."','".$email."','".$username."','".$homepage."',1,'".$ip."')");
	}
}

function cbaff_uplines($id) {
	global $wpdb;	
	$idsponsor = $wpdb->get_var("SELECT `id_referral` FROM `wp_member` WHERE `idwp`=".$id);
	$uplines = $id.','.$idsponsor;
	while ($idsponsor != 0) {
		$getidsponsor = $wpdb->get_var("SELECT `id_referral` FROM `wp_member` WHERE `idwp`=".$idsponsor);
		if ($getidsponsor == 0 || $getidsponsor == $idsponsor ) {
			break;
		} else {
			$uplines = $uplines.','.$getidsponsor;
			$idsponsor = $getidsponsor;
		}
	}
	return $uplines;
}

function urlaff($subdomain) {
	$blogurl = cbdomain();
	if (get_option('affsub') == 1) {
		$result = 'http://'.$subdomain.'.'.$blogurl;
	} else {
		$result = site_url().'/?reg='.$subdomain;
	}

	return $result;
}

function cbdomain() {
	$blogurl = str_replace('https://', '', get_bloginfo('wpurl'));
	$blogurl = str_replace('http://', '', $blogurl);
	if (substr($blogurl,0,4) == 'www.') {
		$blogurl = substr($blogurl, 4);
	}
	return $blogurl;
}

function urlsponsor() {
	$blogurl = cbdomain();
	if (isset($_POST['subdomain']) && $_POST['subdomain'] != '') {
		if (substr($_POST['subdomain'], 0,4) == 'http') {
			header("Location:".$_POST['subdomain']);
		} else {
			if (get_option('affsub') == 1) {
				$url = 'http://'.$_POST['subdomain'].'.'.$blogurl;
			} else {
				$url = site_url().'/?reg='.$_POST['subdomain'];
			}
			header("Location:".$url);
		}
	}
	
	$result = '<form action="" method="post">';
	if (get_option('affsub') == 1) {
		$result .= 'http://<input type="text" name="subdomain" class="subdomain" style="width:30%; display:inline">.'.$blogurl;
	} else {
		$result .= site_url().'/?reg=<input type="text" name="subdomain" class="subdomain">';
	}
	$result .= ' <input type="submit" value="GO"/></form>';
	return $result;
}

add_shortcode('urlsponsor', 'urlsponsor');

function wpse127636_register_url($link){
    /*
        Change wp registration url
    */
    $options = get_option('cb_pengaturan');
    return str_replace(site_url('wp-login.php?action=register', 'login'),site_url('?page_id='.$options['registrasi'], 'login'),$link);
}
add_filter('register','wpse127636_register_url');

function wpse127636_fix_register_urls($url, $path, $orig_scheme){
    /*
        Site URL hack to overwrite register url     
        http://en.bainternet.info/2012/wordpress-easy-login-url-with-no-htaccess
    */
    $options = get_option('cb_pengaturan');
    if ($orig_scheme !== 'login')
        return $url;

    if ($path == 'wp-login.php?action=register')
        return site_url('?page_id='.$options['registrasi'], 'login');

    return $url;
}
add_filter('site_url', 'wpse127636_fix_register_urls', 10, 3);
?>