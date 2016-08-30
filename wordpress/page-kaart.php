<?php get_header(); ?>
	<!--- Example at http://duif.net/spaces/ -->
	<main id="content">
		<h1>Lijst Hackerspaces Nederland</h1>
		<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css" />
		<script src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></script>
		<script src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
		<style>
			#map { height: 550px; width: 500px; }
		</style>
		<div id="map"></div>

		<script>
			// create a map of NL
			var map = L.map('map').setView([52.1460121, 5.4052025], 7);
			// add an OpenStreetMap tile layer
			//L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', { attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors' }).addTo(map);
			L.tileLayer('http://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png', { attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors' }).addTo(map);

			// popup function
			function onEachFeature(feature, layer) {
				// does this feature have a property named popupContent?
				if (feature.properties && feature.properties.popupContent) {
					layer.bindPopup(feature.properties.popupContent);
				}
				else if (feature.properties.name) {
					var html = '<b>'+feature.properties.name+'</b><br/>'+
						feature.properties.address+'<br/>'+
						feature.properties.zip+' '+feature.properties.city+'<br/>'+
						"<a href='"+feature.properties.url+"' target='_blank' >website</a><br/>"+
						feature.properties.description
					layer.bindPopup(html);
				}
				else{
					console.log("oneachfeature");
				};

			}
			// styling funtion
			function pointToLayer(feature, latlon) {
				// from: https://gist.github.com/tmcw/3861338
				//var iconurl = "https://a.tiles.mapbox.com/v3/marker/pin-m-rocket+438fd3.png";
				//"http://localhost:8888/hs.png";

				var iconurl = feature.properties['marker-symbol'];

				return new L.Marker(latlon, {
					icon: new L.icon({
						iconUrl: iconurl,
						iconSize: [30, 70],
						iconAnchor: [15, 35],
						popupAnchor: [0, -25]
					})
				});
			}

			//var geojsonURL = '/nlhackerspaces.geojson';
			var geojsonURL = '/hsmap/hsnl.geojson';
			$.ajax({
				type: "POST",
				url: geojsonURL,
				dataType: 'json',
				success: function (response) {
					geojsonLayer = L.geoJson(response, {
						onEachFeature: onEachFeature,
						pointToLayer: pointToLayer
					}).addTo(map);
				}
			});

			//document.write("<p>Hello World</p>");
			//document.write(			response.length());
			//document.write("<p>Bye World</p>");

		</script>

		<?php while ( have_posts() ) : the_post(); ?>

			<article class="page" id="pageid-<?php the_ID(); ?>">
				
				<?php do_action( 'basic_before_page_title' );  ?>


				<h1><?php the_title(); ?></h1>
				<?php do_action( 'basic_after_page_title' );  ?>

				<?php do_action( 'basic_before_page_content_box' );  ?>
				<div class="entry-box clearfix">
					<?php do_action( 'basic_before_page_content' );  ?>
					<?php the_content(); ?>
					<?php do_action( 'basic_after_page_content' );  ?>
				</div>
				<?php do_action( 'basic_after_page_content_box' );  ?>

			</article>

			<?php 

			if ( comments_open() || get_comments_number() ) { comments_template(); }

		endwhile; ?>
	</main> <!-- #content -->
	<?php get_sidebar(); ?>
<?php get_footer(); ?>