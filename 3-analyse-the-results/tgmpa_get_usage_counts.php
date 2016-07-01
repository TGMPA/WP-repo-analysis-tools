<?php

class Get_Tgmpa_Active_Install_Counts {

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
		'themes'  => 'U:/WordPress-Theme-Directory-Slurper/themes',
		'plugins' => 'U:/WordPress-Plugin-Directory-Slurper/plugins',
	);

	protected $prefix = array(
		'themes'  => 'latest-themes',
		'plugins' => 'latest-plugins',
	);

	protected $slugs = array(
		'themes'  => array(),
		'plugins' => array(),
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

	protected $totals = array(
		'themes'  => 0,
		'plugins' => 0,
	);

	protected $installs = array(
		'themes'  => array(),
		'plugins' => array(),
	);

	protected $downloads = array(
		'themes'  => array(),
		'plugins' => array(),
	);

	protected $authors = array(
		'themes'  => array(),
		'plugins' => array(),
	);

	protected $fields = array(
		'description'     => false,
		'sections'        => false,
		'rating'          => false,
		'ratings'         => false,
		'downloaded'      => true,
		'downloadlink'    => false,
		'last_updated'    => false,
		'homepage'        => false,
		'tags'            => false,
		'template'        => false,
		'parent'          => false,
		'versions'        => false,
		'screenshot_url'  => false,
		'active_installs' => true,
	);


	public function __construct() {
		ini_set( 'max_execution_time', 1800 ); // Half an hour

		$this->extract_unique_slugs();

		foreach ( $this->slugs as $type => $slugs ) {
			$this->totals[ $type ] = count( $slugs );

			foreach ( $slugs as $slug ) {
				if ( $type === 'themes' ) {
					$this->do_theme_api_call( $slug );
				}
				if ( $type === 'plugins' ) {
					$this->do_plugin_api_call( $slug );
				}
			}
		}

		$this->generate_output();
	}

	protected function extract_unique_slugs() {
		foreach( $this->prefix as $type => $pf ) {
			foreach( $this->file_patterns as $pattern ) {
				$file  = sprintf( $pattern, $pf );
				$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
				foreach ( $lines as $line ) {
					$line = trim( $line );
					$line = str_replace( '\\', '/', $line );
					if ( preg_match( '`(?:^|/)' . $type . '/([^/]+)/`', $line, $match ) ) {
						$slug = $match[1];
						if ( ! empty( $slug ) && ! in_array( $slug, $this->exclude[ $type ], true ) ) {
							$this->slugs[ $type ][ $slug ] = $slug;
						}
					}
				}
			}
		}
	}

	protected function do_theme_api_call( $slug ) {
		$args = ( array(
			'slug' 	 => $slug,
			'fields' => $this->fields
		) );

		$data = array(
			'action'  => 'theme_information',
			'request' => $args
		);


		$response = file_get_contents( 'https://api.wordpress.org/themes/info/1.1/?' . http_build_query( $data ) );

		if ( empty( $response ) || ! is_string( $response ) ) {
			echo ' Unable to fetch theme ' . $slug . "\n";
			return;
		}

		$response = json_decode( $response );

		$this->downloads['themes'][ $slug ] = $response->downloaded;
		$this->installs['themes'][ $slug ]  = $response->active_installs;
		$this->authors['themes'][ $slug ]   = $response->author;
	}

	protected function do_plugin_api_call( $slug ) {

		$response = file_get_contents( 'https://api.wordpress.org/plugins/info/1.0/' . $slug. '.json?fields=active_installs' );

		if ( empty( $response ) || ! is_string( $response ) || $response === 'null' ) {
			echo ' Unable to fetch plugin ' . $slug . "<br />\n";
			return;
		}

		$response = json_decode( $response );

		$this->downloads['plugins'][ $slug ] = $response->downloaded;
		$this->installs['plugins'][ $slug ]  = $response->active_installs;
		$this->authors['plugins'][ $slug ]   = str_replace( 'https://profiles.wordpress.org/', '', $response->author_profile );
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
			<th width="30%">&nbsp;</th>
			<th width="35%">Themes</th>
			<th width="35%">Plugins</th>
		</tr>
		</thead>
		<tbody>';

		$this->generate_stats();
		$this->generate_install_top10();
		$this->generate_download_top10();
		$this->generate_author_top10();

		echo '
		</tbody>
	</table>
</body>
</html>';
	}

	protected function generate_stats() {
		echo '
		<tr>
			<th>Usage count</th>';

		foreach ( $this->totals as $type => $count ) {
			echo '
			<td><span>', number_format( $count ), '</span></td>';
		}

		echo '
		</tr>';

		echo '
		<tr>
			<th>Usage stats</th>';

		foreach ( $this->totals as $type => $count ) {
			$total = count( glob( $this->dirs[ $type ] . '/*', GLOB_ONLYDIR ) );
			$perc  = ( $count / $total ) * 100;
			echo '
			<td><span>', number_format( $perc, 2 ), '% of ', number_format( $total ), '</span></td>';
		}

		echo '
		</tr>';

		echo '
		<tr>
			<th>Total nr of active installs</th>';

		foreach ( $this->installs as $type => $counts ) {
			echo '
			<td><span>', number_format( array_sum( $counts ) ), '</span></td>';
		}

		echo '
		</tr>';

		echo '
		<tr>
			<th>Total nr of downloads</th>';

		foreach ( $this->downloads as $type => $counts ) {
			echo '
			<td><span>', number_format( array_sum( $counts ) ), '</span></td>';
		}

		echo '
		</tr>';

		echo '
		<tr>
			<th>Nr of unique authors</th>';

		foreach ( $this->authors as $type => $authors ) {
			$unique_authors = array_unique( $authors );
			echo '
			<td><span>', number_format( count( $unique_authors ) ), '</span></td>';
		}

		echo '
		</tr>';
	}

	protected function generate_install_top10() {
		$this->generate_top10( 'Most popular by installs', $this->installs );
	}

	protected function generate_download_top10() {
		$this->generate_top10( 'Most popular by downloads', $this->downloads );
	}

	protected function generate_author_top10() {
		$author_sums = array();
		foreach ( $this->authors as $type => $authors ) {
			$author_sums[ $type ] = array_count_values( $authors );
		}

		$this->generate_top10( 'Most used by authors', $author_sums );
	}

	protected function generate_top10( $row_title, $array ) {
		echo '
		<tr>
			<th>', $row_title, '</th>';

		foreach ( $array as $type => $counts ) {
			echo '
			<td>
				<ol>';

			arsort( $counts, SORT_NUMERIC );
			$top10 = array_slice( $counts, 0, 10 );

			foreach ( $top10 as $slug => $count ) {
				echo '
					<li>', $slug, ' : <span>', number_format( $count ), '</span></li>';
			}
			echo '
				</ol>
			</td>';
		}

		echo '
		</tr>';
	}

}

new Get_Tgmpa_Active_Install_Counts;
