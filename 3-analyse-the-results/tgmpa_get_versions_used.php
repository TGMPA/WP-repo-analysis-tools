<?php

class Get_Tgmpa_Versions_In_Use {

	/**
	 * IMPORTANT: ADJUST THE PATHS TO YOUR LOCAL SETUP!
	 */
	protected $file_patterns = array(
		'U:\TGMPA analysis results\%s TGMPA search partial file name.log',
		'U:\TGMPA analysis results\%s TGMPA search COMBINED.log',
	);

	/**
	 * IMPORTANT: ADJUST THE PATHS TO YOUR LOCAL SETUP!
	 */
	protected $dirs = array(
		'themes'  => 'U:/WordPress-Theme-Directory-Slurper/',
		'plugins' => 'U:/WordPress-Plugin-Directory-Slurper/',
	);
	
	protected $prefix = array(
		'themes'  => 'latest-themes',
		'plugins' => 'latest-plugins',
	);

	protected $exclude = array(
		'themes' => array(
			'aviator',
			'clearly',
			'current',
			'estate',
			'portal',
			'puro',
			'siteorigin-north',
			'toothpaste',
			'ultra',
			'vantage',
		),
		'plugins' => array(
			'recommended-links',
			'siteorigin-panels',
		),
	);

	protected $seen = array();

	protected $versions = array();

	protected $failed = array(
		'themes'  => array(),
		'plugins' => array(),
	);

	protected $totals = array(
		'themes'  => 0,
		'plugins' => 0,
	);


	public function __construct() {
		ini_set( 'max_execution_time', 1800 ); // Half an hour

		foreach( $this->prefix as $type => $pf ) {
			foreach( $this->file_patterns as $pattern ) {
				$file  = sprintf( $pattern, $pf );
				$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
				foreach ( $lines as $line ) {
					$this->analyse_file( $line, $type );
				}
			}
		}

		$this->make_unique();

		$this->generate_output();

		echo '<h3>Version details:</h3>';
		var_dump( $this->versions );
		echo '<h3>Failed to retrieve version details for:</h3>';
		var_dump( $this->failed );
	}

	protected function analyse_file( $file_name, $type ) {
		$file_name = trim( $file_name );
		if ( empty( $file_name ) ) {
			return;
		}

		foreach( $this->exclude[ $type ] as $exclude ) {
			if ( strpos( $file_name, "$type\\$exclude" ) !== false ) {
				// Ignore, not one of ours.
				return;
			}
		}

		if ( strpos( $file_name, 'U:' ) !== 0 ) {
			$file_name = $this->dirs[ $type ] . $file_name;
		}
		
		$file_name = $this->wp_normalize_path( $file_name );
		
		if ( isset( $this->seen[ $file_name ] ) ) {
			// Already checked.
			return;
		} else {
			$this->seen[ $file_name ] = $file_name;
		}

		// Get first 8kiB of the file.
		$start_of_file = file_get_contents( $file_name, null, null, 0, 8192 );

		// Try and find the TGMPA version number.
		if ( preg_match( '`const TGMPA_VERSION = \'([^\']+)\';`', $start_of_file, $match ) === 1 ) {
			$this->versions[ trim( $match[1] ) ][ $type ][] = $file_name;
		} else if ( preg_match( '`TGM-Plugin-Activation\s+\* @version\s+(.+)`', $start_of_file, $match ) === 1 ) {
			$this->versions[ trim( $match[1] ) ][ $type ][] = $file_name;
		} else {
			$this->failed[ $type ][] = $file_name;
		}
	}


	protected function make_unique() {
		foreach( $this->versions as $version => $types ) {
			foreach( $types as $type => $files ) {
				$this->versions[ $version ][ $type ] = array_unique( $files );
			}
		}
	}
	

	// Taken from WP core.
	protected function wp_normalize_path( $path ) {
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );
		if ( ':' === substr( $path, 1, 1 ) ) {
			$path = ucfirst( $path );
		}
		return $path;
	}


    protected function generate_output() {
		echo '
<html>
<head>
	<style type="text/css">
		* {
			font-family: Verdana;
			font-size: 12px;
		}
		table {
			width: 80%;
		}
		tbody th {
			text-align: left;
			vertical-align: top;
		}
		th, td {
			padding: 3px 10px 3px 5px;
			border-bottom: 1px solid #DDDDDD;
		}
		td span {
			float: right;
			margin-right: 25px;
		}
	</style>
</head>
<body>
	<table>
		<thead>
		<tr>
			<th width="30%">Version</th>
			<th width="35%">Themes</th>
			<th width="35%">Plugins</th>
		</tr>
		</thead>
		<tbody>';

		$this->generate_version_stats();

		echo '
		</tbody>
	</table>
</body>
</html>';
	}

	protected function generate_version_stats() {
		uksort( $this->versions, 'version_compare' );

		foreach( $this->versions as $version => $details ) {
			echo '
		<tr>
			<th>', $version, '</th>';

			if ( isset( $details['themes'] ) ) {
				$count = count( $details['themes'] );
				echo '
			<td><span>', number_format( $count ), '</span></td>';
				$this->totals['themes'] += $count;
			}
			else {
				echo '
			<td>&nbsp;</td>';
			}

			if ( isset( $details['plugins'] ) ) {
				$count = count( $details['plugins'] );
				echo '
			<td><span>', number_format( $count ), '</span></td>';
				$this->totals['plugins'] += $count;
			}
			else {
				echo '
			<td>&nbsp;</td>';
			}

			echo '
		</tr>';
		}
		
		echo '
		<tr>
			<th>Total:</th>
			<td><span><strong>', number_format( $this->totals['themes'] ), '</strong></span></td>
			<td><span><strong>', number_format( $this->totals['plugins'] ), '</strong></span></td>
		</tr>';
	}

}

new Get_Tgmpa_Versions_In_Use;
