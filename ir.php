<?php
/*
	https://github.com/kasparsd/ir-pdf

	Žurnāla "Ir" izdevēji nepiedāvā saviem abonentiem vienkāršu veidu, 
	kā to automātiski saņemt un lasīt savā elektroniskajā lasītājā 
	(iPad, Kindle, u.c.) uzreiz, kad ir izdots jaunākais tā numurs.

	Šis rīks automātiski atrod jaunākā izdevuma numuru ir.lv, 
	pievienojas ir.lv ar tavu lietotāja vārdu un paroli, un lejuplādē 
	izdevuma lappuses JPG formātā, saglabā tās vienā PDF failā un nosūta 
	uz norādīto epasta adresi.

	Šis rīks ir paredzēts TIKAI UN VIENĪGI personīgai lietošanai.
*/

$config = array(
		'email_to' => 'example@example.com, another@example.com', // PDF saņēmējs, piem.: 'name@example.com, another@example.com'
		'username' => '', // tavs ir.lv pieejas epasts, piem.: name@example.com
		'password' => '', // tava ir.lv pieejas parole
		'url_id' => 'http://www.ir.lv/zurnals/pedejais',
		'url_login' => 'http://www.ir.lv/lietotajs/ienakt',
		'url_index' => 'http://www.ir.lv/zurnals/' // izmantots kā http://www.ir.lv/zurnals/$id
	);


// Extract the current issue ID

$req = file_get_contents( $config['url_id'] );

foreach ( $http_response_header as $header ) {
	if ( strstr( $header, 'Location:' ) ) {
		$id = preg_replace( '/[^\d]/', '', $header );
		break;
	}
}

if ( empty( $id ) )
	die('ID not found!');

if ( file_exists( $id . '.pdf' ) )
	die('PDF already exists!');

// Unset because $http_response_header is in local scope
unset( $http_response_header );


// Extract the auth token

$login_page = file_get_contents( $config['url_login'] );

$doc = new DOMDocument();
$doc->loadHTML( $login_page );

foreach ( $doc->getElementsByTagName('input') as $element ) {
	if ( $element->getAttribute('name') == 'authenticity_token' ) {
		$token = $element->getAttribute('value');
		break;
	}
}


// Extract the session cookie

$cookies = '';

foreach ( $http_response_header as $s )
	if ( preg_match( '|^Set-Cookie:\s*([^=]+)=([^;]+);(.+)$|', $s, $parts ) )
		$cookies .= sprintf( ' %s=%s;', $parts[1], $parts[2] );

unset( $http_response_header );	


// Build the login request

$post = array( 
	'authenticity_token' => $token,
	'user' => array(
		'email' => $config['username'],
		'password' => $config['password']
	)
);

$req = array(
	'http' => array(
		'method' => 'POST',
		'content' => http_build_query( $post ),
		'header' => 'Cookie:' . $cookies
	)
);

file_get_contents( $config['url_login'], false, stream_context_create( $req ) );


// Extract the authentication cookie

$cookies = '';

foreach ( $http_response_header as $s )
	if ( preg_match( '|^Set-Cookie:\s*([^=]+)=([^;]+);(.+)$|', $s, $parts ) )
		$cookies .= sprintf( ' %s=%s;', $parts[1], $parts[2] );


// Download the index page for the current issue

$req = array(
	'http' => array(
		'method' => 'GET',
		'header' => 'Cookie: ' . $cookies . "\r\n"
	)
);

$o = file_get_contents( $config['url_index'] . $id, false, stream_context_create( $req ) );


// Extract the pages of the current issue

$doc = new DOMDocument();
    $doc->loadHTML( $o );

$pages = array();

    foreach ( $doc->getElementsByTagName('img') as $element )
            if ( $element->getAttribute('class') == 'th' )
                    $pages[] = $element->getAttribute('src');

if ( empty( $pages ) )
	die('No images found');


// Create a folder for storing the pages for the current issue

if ( ! is_dir( $id ) )
	mkdir( $id );


// Extract and download pages of the current issue as JPG files

foreach ( $pages as $src ) {
	if ( strstr( $src, '/th-' ) ) {
		if ( file_exists($id . '/' . basename($src) ) )
			continue;

		$img = file_get_contents( 'http://www.ir.lv' . str_replace( '/th-', '/full-', $src ) );
		file_put_contents( $id . '/' . basename( $src ), $img );
	}
}


// Build the PDF from images using ImageMagick

if ( ! file_exists( $id . '.pdf' ) )
	exec( sprintf( 'convert %d/*.jpg %d.pdf', $id, $id ) );

if ( ! file_exists( $id . '.pdf' ) )
	die('PDF was not saved!');


// Email the PDF

$separator = md5( time() );
$attachment = chunk_split( base64_encode( file_get_contents( $id . '.pdf' ) ) );

$headers = array(
	'MIME-Version: 1.0',
	'Content-Type: multipart/related; boundary="' . $separator . '"',
);

$message = array(
	'--' . $separator,
	'Content-Type: application/pdf; name="' . $id . '.pdf"',
	'Content-Transfer-Encoding: base64',
	'Content-Disposition: attachment; filename="'. $id .'.pdf"' . PHP_EOL,
	$attachment . PHP_EOL,
	'Content-Type: multipart/alternative',
	'--' . $separator . '--' . PHP_EOL,
);

mail( $config['email_to'], 'IR #' . $id, implode( PHP_EOL, $message ), implode( PHP_EOL, $headers ) );
