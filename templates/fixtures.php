<table>
	<thead>
		<tr>
			<th>/</th>
			<?php
				foreach ( $fixture_order as $team ) {
					printf( '<th>%s</th>', get_the_title( $team ) );
				}
			?>
		</tr>
	</thead>
	<tbody><?php
		foreach ( $fixture as $home_team => $home_data ) {
			echo '<tr>';
			printf( '<th>%s</th>', get_the_title( $home_team ) );
			foreach ( $fixture_order as $away_team ) {
				if ( absint( $away_team ) === absint( $home_team ) ) {
					echo '<td style="background-color:#ccc;"></td>';
				} elseif ( isset( $home_data[ $away_team ] ) ) {
					printf( '<td>%s</td>', $home_data[ $away_team ] );
				} else {
					echo '<td>-</td>';
				}
			}
			echo '</tr>';
		}
	?></tbody>
</table>