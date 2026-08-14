<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-demand-page">
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header pds-demand-header">
                        <div>
                            <h4 class="card-title pds-page-title mb-0">{{ $pageTitle ?? __('message.high_demanding_areas') }}</h4>
                            <p class="pds-demand-subtitle mb-0">View demand zones and rider availability on the map</p>
                        </div>
                    </div>

                    <div class="card-body pds-page-body pds-demand-body">
                        <div class="pds-demand-controls">
                            <div class="pds-demand-legend" id="map-filter-menu" role="group" aria-label="Demand level filters">
                                @foreach ([
                                    'high' => ['label' => 'Very high', 'hint' => 'Few riders available', 'color' => '#EF4444'],
                                    'moderate' => ['label' => 'Moderate', 'hint' => 'Needs more riders', 'color' => '#F97316'],
                                    'normal' => ['label' => 'Normal', 'hint' => 'Well balanced', 'color' => '#EAB308'],
                                    'low' => ['label' => 'Low', 'hint' => 'No action needed', 'color' => '#22C55E'],
                                ] as $level => $meta)
                                    <button type="button"
                                        class="pds-demand-legend__item pds-demand-legend__item--{{ $level }} filter-button {{ $level }} active"
                                        data-level="{{ $level }}">
                                        <span class="pds-demand-legend__dot" style="--dot-color: {{ $meta['color'] }}"></span>
                                        <span class="pds-demand-legend__text">
                                            <strong>{{ $meta['label'] }}</strong>
                                            <small>{{ $meta['hint'] }}</small>
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="pds-demand-filters">
                                <div class="pds-demand-filter">
                                    <label class="pds-demand-filter__label" for="country_id">{{ __('message.country') }}</label>
                                    {{ html()->select('country_id', isset($data) ? [$data->country->id => $data->country->name] : [], old('country_id'))->class('select2js country_id')->id('country_id')->attribute('data-placeholder', __('message.country'))->attribute('data-ajax--url', route('ajax-list', ['type' => 'country-list'])) }}
                                </div>
                                <div class="pds-demand-filter">
                                    <label class="pds-demand-filter__label" for="city_id">{{ __('message.city') }}</label>
                                    {{ html()->select('city_id', isset($data) ? [$data->city->id => $data->city->name] : [], old('city_id'))->class('select2js city_id')->id('city_id')->attribute('data-placeholder', __('message.city')) }}
                                </div>
                            </div>
                        </div>

                        <div class="pds-demand-map-wrap" id="map-container">
                            <div id="map" class="pds-demand-map"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
        <script>
            const zones = @json($zones);
            const riders = @json($riders);

            let map;
            let zoneCircles = [];
            let riderMarkers = [];

            const getColorByLevel = level => ({
                high: '#EF4444',
                moderate: '#F97316',
                normal: '#EAB308',
                low: '#22C55E'
            }[level] || '#22C55E');

            const haversineDistance = (lat1, lon1, lat2, lon2) => {
                const R = 6371;
                const toRad = deg => deg * Math.PI / 180;
                const dLat = toRad(lat2 - lat1);
                const dLon = toRad(lon2 - lon1);
                const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
                return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            };

            const groupZones = (zones, threshold = 10) => {
                const clusters = [];

                zones.forEach(zone => {
                    let cluster = clusters.find(c =>
                        haversineDistance(c.center.lat, c.center.lng, zone.lat, zone.lng) <= threshold
                    );

                    if (cluster) {
                        const count = cluster.zones.length;
                        cluster.center.lat = (cluster.center.lat * count + zone.lat) / (count + 1);
                        cluster.center.lng = (cluster.center.lng * count + zone.lng) / (count + 1);
                        cluster.zones.push(zone);
                    } else {
                        clusters.push({
                            center: { lat: zone.lat, lng: zone.lng },
                            zones: [zone]
                        });
                    }
                });

                return clusters;
            };

            let activeZoneCenter = null;

            const initMap = () => {
                map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 3,
                    center: { lat: 20.0, lng: 0.0 },
                    mapTypeControl: true,
                    streetViewControl: false,
                    fullscreenControl: true,
                });

                const clusteredZones = groupZones(zones);

                clusteredZones.forEach(({ center, zones }) => {
                    const level = ['high', 'moderate', 'normal', 'low'].find(l => zones.some(z => z.level === l)) || 'low';

                    const circle = new google.maps.Circle({
                        strokeColor: getColorByLevel(level),
                        strokeOpacity: 0.85,
                        strokeWeight: 2,
                        fillColor: getColorByLevel(level),
                        fillOpacity: 0.28,
                        map,
                        center,
                        radius: 5000
                    });

                    circle.zoneLevel = level;
                    zoneCircles.push(circle);

                    circle.addListener('click', () => {
                        map.setCenter(center);
                        map.setZoom(12);
                        activeZoneCenter = center;
                        updateRiderMarkers();
                    });
                });

                riders.forEach(rider => {
                    const position = {
                        lat: parseFloat(rider.lat),
                        lng: parseFloat(rider.lng)
                    };
                    const icon = `http://maps.google.com/mapfiles/ms/icons/${rider.is_available ? 'green' : 'red'}-dot.png`;

                    const marker = new google.maps.Marker({
                        position,
                        icon,
                        title: `Name: ${rider.name}`,
                        map: null
                    });

                    marker.addListener('click', () => {
                        new google.maps.InfoWindow({
                            content: `Name: ${rider.name}<br>Status: ${rider.is_available ? 'Available' : 'Busy'}`
                        }).open(map, marker);
                    });

                    riderMarkers.push(marker);
                });

                map.addListener('zoom_changed', updateRiderMarkers);
                map.addListener('dragend', updateRiderMarkers);
            };

            const updateRiderMarkers = () => {
                if (!activeZoneCenter || map.getZoom() < 11) {
                    riderMarkers.forEach(marker => marker.setMap(null));
                    return;
                }

                riderMarkers.forEach(marker => {
                    const dist = haversineDistance(
                        activeZoneCenter.lat,
                        activeZoneCenter.lng,
                        marker.getPosition().lat(),
                        marker.getPosition().lng()
                    );
                    marker.setMap(dist <= 10 ? map : null);
                });
            };

            const loadCityList = (countryId) => {
                const route = "{{ route('ajax-list', ['type' => 'extra_charge_city', 'country_id' => '']) }}" + countryId;
                $.ajax({
                    url: route.replace('amp;', ''),
                    success: result => {
                        $('#city_id').select2({
                            width: '100%',
                            placeholder: "{{ __('message.select_name', ['select' => __('message.city')]) }}",
                            data: result.results
                        });
                    }
                });
            };

            $(document).ready(function() {
                const geocoder = new google.maps.Geocoder();

                $('.select2js').select2({ width: '100%' });

                $('#country_id').on('change', function() {
                    const countryName = $('#country_id option:selected').text();
                    $('#city_id').empty().trigger('change');
                    loadCityList($(this).val());

                    if (countryName) {
                        geocoder.geocode({ address: countryName }, (results, status) => {
                            if (status === 'OK') {
                                map.setCenter(results[0].geometry.location);
                                map.setZoom(6);
                            }
                        });
                    }
                });

                $('#city_id').on('change', function() {
                    const cityName = $('#city_id option:selected').text();
                    if (!cityName) return;

                    geocoder.geocode({ address: cityName }, (results, status) => {
                        if (status === 'OK') {
                            map.setCenter(results[0].geometry.location);
                            map.setZoom(12);
                        }
                    });
                });

                $('.filter-button').on('click', function() {
                    $(this).toggleClass('active');
                    const selectedLevels = $('.filter-button.active').map(function() {
                        return $(this).data('level');
                    }).get();

                    zoneCircles.forEach(circle => {
                        circle.setMap(selectedLevels.includes(circle.zoneLevel) ? map : null);
                    });
                });
            });

            $(window).on('load', initMap);
        </script>
    @endsection
</x-master-layout>
