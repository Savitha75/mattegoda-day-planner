// FR-15: map centred on the place, one marker at its coordinates
(function () {
  'use strict';

  var el = document.getElementById('place-map');
  if (!el || typeof L === 'undefined') {
    return;                                   // no map on this page, or Leaflet failed to load
  }

  var lat  = parseFloat(el.dataset.lat);
  var lng  = parseFloat(el.dataset.lng);
  var name = el.dataset.name;

  var map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 15);

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  L.marker([lat, lng], { title: name, alt: name }).addTo(map);
})();