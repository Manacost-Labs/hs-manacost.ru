/* global Chart, hsDeckStatsData */
( function () {
	const METRICS = [
		{
			key: 'views',
			label: 'Views',
			borderColor: '#2271b1',
			backgroundColor: 'rgba(34, 113, 177, 0.10)',
		},
		{
			key: 'copies',
			label: 'Copies',
			borderColor: '#00a32a',
			backgroundColor: 'rgba(0, 163, 42, 0.10)',
		},
		{
			key: 'clicks',
			label: 'Clicks',
			borderColor: '#d63638',
			backgroundColor: 'rgba(214, 54, 56, 0.10)',
		},
	];

	function onDomReady( callback ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', callback );
			return;
		}

		callback();
	}

	function toArray( value ) {
		return Array.isArray( value ) ? value : [];
	}

	function createDataset( metric, source ) {
		return {
			label: metric.label,
			data: toArray( source[ metric.key ] ),
			borderColor: metric.borderColor,
			backgroundColor: metric.backgroundColor,
			fill: true,
			tension: 0.2,
			pointRadius: 2,
		};
	}

	function initStatsChart() {
		if ( typeof Chart === 'undefined' ) {
			return;
		}

		if ( typeof hsDeckStatsData === 'undefined' || ! hsDeckStatsData ) {
			return;
		}

		const canvas = document.getElementById( 'hs-deck-chart' );
		if ( ! canvas ) {
			return;
		}

		new Chart( canvas, {
			type: 'line',
			data: {
				labels: toArray( hsDeckStatsData.labels ),
				datasets: METRICS.map( function ( metric ) {
					return createDataset( metric, hsDeckStatsData );
				} ),
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: {
					mode: 'index',
					intersect: false,
				},
				plugins: {
					legend: {
						position: 'top',
					},
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							precision: 0,
						},
					},
				},
			},
		} );
	}

	onDomReady( initStatsChart );
} )();
