// FR-18 to FR-22: map of all active places, centred on Salgas Junction
(function () {
  'use strict';

  var el      = document.getElementById('places-map');
  var countEl = document.getElementById('map-count');
  if (!el || typeof L === 'undefined') {
    return;
  }

  // FR-18, FR-19: OpenStreetMap tiles, centred on Salgas Junction
  var start = [parseFloat(el.dataset.startLat), parseFloat(el.dataset.startLng)];
  var map = L.map(el).setView(start, 11);

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  // FR-19: distinct start marker
  var startIcon = L.divIcon({
    className: 'start-marker', html: '<span>S</span>',
    iconSize: [30, 30], iconAnchor: [15, 15], popupAnchor: [0, -15]
  });
  L.marker(start, { icon: startIcon, title: 'Salgas Junction (start)', alt: 'Start: Salgas Junction', zIndexOffset: 1000 })
    .addTo(map)
    .bindPopup('<strong>Salgas Junction, Mattegoda</strong><br>Starting point');

  // Only accept colours like #2E7D32 (never put untrusted text into HTML)
  function safeColour(c) {
    return /^#[0-9A-Fa-f]{6}$/.test(c) ? c : '#6c757d';
  }

  function capitalise(s) {
    return s.charAt(0).toUpperCase() + s.slice(1);
  }

  // FR-20: round marker in the primary category's colour
  function placeIcon(colour) {
    return L.divIcon({
      className: 'place-marker', html: '<span style="background-color:' + safeColour(colour) + '"></span>',
      iconSize: [22, 22], iconAnchor: [11, 11], popupAnchor: [0, -11]
    });
  }

  // FR-21: popup built with DOM methods + textContent, so names can never run as HTML
  function popupFor(place) {
    var box = document.createElement('div');

    var title = document.createElement('strong');
    title.textContent = place.name;
    box.appendChild(title);

    var cats = document.createElement('div');
    cats.className = 'my-1';
    place.categories.forEach(function (c) {
      var badge = document.createElement('span');
      badge.className = 'badge badge-cat me-1';
      badge.style.backgroundColor = safeColour(c.colour);
      badge.textContent = capitalise(c.name);
      cats.appendChild(badge);
    });
    box.appendChild(cats);

    var dist = document.createElement('div');
    dist.className = 'small text-muted mb-1';
    dist.textContent = place.distanceKm !== null
      ? place.distanceKm.toFixed(1) + ' km from Salgas Junction'
      : 'Distance not available';
    box.appendChild(dist);

    var link = document.createElement('a');
    link.href = place.url;
    link.textContent = 'View details';
    box.appendChild(link);

    return box;
  }

  // FR-20, FR-22: load the (filtered) places from the API
  fetch(el.dataset.api, { headers: { Accept: 'application/json' } })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }
      return response.json();
    })
    .then(function (data) {
      data.places.forEach(function (place) {
        if (place.lat === null || place.lng === null) {
          return;
        }
        var primaryColour = place.categories.length ? place.categories[0].colour : '#6c757d';
        L.marker([place.lat, place.lng], { icon: placeIcon(primaryColour), title: place.name, alt: place.name })
          .addTo(map)
          .bindPopup(popupFor(place));
      });
      countEl.textContent = data.count + (data.count === 1 ? ' place' : ' places') + ' shown on the map';
    })
    .catch(function () {
      countEl.textContent = 'Places could not be loaded. Please refresh the page.';
      countEl.classList.add('text-danger');
    });
})();