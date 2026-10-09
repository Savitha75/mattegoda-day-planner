// FR-23 to FR-25: the road route for the current plan, on the map of the plan page
var PlanRoute = (function () {
  'use strict';

  var box     = document.getElementById('plan-map');
  var summary = document.getElementById('route-summary');
  if (!box || !summary || typeof L === 'undefined') {
    return { update: function () {} };                 // no map on this page
  }

  var WAIT_MS = 12000;   // the server gives up after 10 s (FR-25); this is only a safety net
  var start   = [parseFloat(box.dataset.startLat), parseFloat(box.dataset.startLng)];

  var map = L.map(box).setView(start, 11);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors · ' +
                 'Routes: <a href="https://project-osrm.org/">OSRM</a>'
  }).addTo(map);

  // Start marker (same style as the main map)
  L.marker(start, {
    icon: L.divIcon({ className: 'start-marker', html: '<span>S</span>', iconSize: [30, 30], iconAnchor: [15, 15] }),
    title: 'Salgas Junction (start)', alt: 'Start: Salgas Junction', zIndexOffset: 1000
  }).addTo(map);

  var layer   = L.layerGroup().addTo(map);   // numbered stops + route line, redrawn on each change
  var lastKey = null;                        // stop order that is currently drawn
  var current = null;                        // the request in progress

  // 65 -> "1 h 5 min"
  function formatMinutes(min) {
    var h = Math.floor(min / 60);
    var m = min % 60;
    return (h > 0 ? h + ' h ' : '') + m + ' min';
  }

  function showSummary(text, className) {
    summary.className = className;
    summary.textContent = text;
  }

  // Popup text built with textContent (a place name is never treated as HTML)
  function popupFor(number, name) {
    var div = document.createElement('div');
    div.textContent = number + '. ' + name;
    return div;
  }

  function update(stops, places) {
    var key = stops.join(',');
    if (key === lastKey) {
      return;                                // only the date or time changed: same route
    }
    lastKey = key;
    if (current) {
      current.abort();                       // the order changed: cancel the old request
    }
    layer.clearLayers();

    // FR-25: the stop markers are drawn first, so they stay even if routing fails
    var bounds = [start];
    stops.forEach(function (id, i) {
      var p = places[id];
      if (!p) { return; }
      L.marker([p.lat, p.lng], {
        icon: L.divIcon({ className: 'stop-marker', html: '<span>' + (i + 1) + '</span>', iconSize: [26, 26], iconAnchor: [13, 13] }),
        title: (i + 1) + '. ' + p.name, alt: 'Stop ' + (i + 1) + ': ' + p.name
      }).bindPopup(popupFor(i + 1, p.name)).addTo(layer);
      bounds.push([p.lat, p.lng]);
    });
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });

    if (stops.length === 0) {
      showSummary('Add places to your plan to see the route.', 'text-muted mt-2 mb-0');
      return;
    }

    showSummary('Calculating the route…', 'text-muted mt-2 mb-0');
    var request = new AbortController();
    current = request;
    var timer = setTimeout(function () { request.abort(); }, WAIT_MS);

    fetch(box.dataset.routeApi + '?ids=' + key, {
      headers: { Accept: 'application/json' },
      signal: request.signal
    })
      .then(function (response) {
        if (!response.ok) { throw new Error('HTTP ' + response.status); }
        return response.json();
      })
      .then(function (route) {
        if (request !== current) { return; }          // a newer order replaced this one
        // FR-23: the road route, in plan order
        L.polyline(route.geometry, { color: '#198754', weight: 5, opacity: 0.8 }).addTo(layer);
        // FR-24: totals
        showSummary('Total: ' + route.distanceKm.toFixed(1) + ' km · about ' +
                    formatMinutes(route.durationMin) + ' driving', 'fw-semibold mt-2 mb-0');
      })
      .catch(function () {
        if (request !== current) { return; }          // cancelled on purpose: ignore
        // FR-25: exact SRS message; the markers above stay on the map
        showSummary('Route guidance is temporarily unavailable.', 'alert alert-warning mt-2 mb-0');
        lastKey = null;                                // try again on the next change
      })
      .finally(function () {
        clearTimeout(timer);
      });
  }

  return { update: update };
})();