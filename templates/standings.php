<table>
	<thead>
		<tr>
			<th>Team</th>
			<th>M</th>
			<th>W</th>
			<th>D</th>
			<th>L</th>
			<th>GD</th>
			<th>Pts</th>
		</tr>
	</thead>
	<tbody><?php
		foreach ( $standings as $team => $data ) {
			?>
			<tr>
				<td><?php echo get_the_title( $team ); ?></td>
				<td><?php echo $data['m']; ?></td>
				<td><?php echo $data['w']; ?></td>
				<td><?php echo $data['d']; ?></td>
				<td><?php echo $data['l']; ?></td>
				<td><?php echo $data['gf'] . ' : ' . $data['ga']; ?></td>
				<td><?php echo $data['p']; ?></td>
			</tr>
			<?php
		}
	?></tbody>
</table>