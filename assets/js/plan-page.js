// FR-28: show the current plan with a Remove button for each stop
(function () {
  'use strict';

  var root = document.getElementById('plan-app');
  if (!root || typeof DayPlan === 'undefined') {
    return;
  }

  var places = {};          // place id -> place data from the API

  function el(tag, className, text) {
    var e = document.createElement(tag);
    if (className) { e.className = className; }
    if (text !== undefined) { e.textContent = text; }
    return e;
  }

  function render() {
    var plan = DayPlan.load();
    root.textContent = '';

    // Empty plan
    if (plan.stops.length === 0) {
      var box = el('div', 'text-center py-5');
      box.appendChild(el('p', 'lead mb-3', 'Your plan is empty.'));
      var toList = el('a', 'btn btn-success me-2', 'Browse places');
      toList.href = root.dataset.listUrl;
      var toMap = el('a', 'btn btn-outline-success', 'Open the map');
      toMap.href = root.dataset.mapUrl;
      box.appendChild(toList);
      box.appendChild(toMap);
      root.appendChild(box);
      return;
    }

    var n = plan.stops.length;
    root.appendChild(el('p', 'mb-2', n + (n === 1 ? ' stop' : ' stops') + ' in your plan'));

    var list = el('ol', 'list-group list-group-numbered mb-3');
    plan.stops.forEach(function (id) {
      var place = places[id];
      if (!place) { return; }

      var item = el('li', 'list-group-item d-flex justify-content-between align-items-start gap-2');
      var info = el('div', 'ms-2 me-auto');
      var link = el('a', 'fw-semibold text-decoration-none', place.name);
      link.href = place.url;
      info.appendChild(link);
      info.appendChild(el('div', 'small text-muted',
        place.distanceKm !== null ? place.distanceKm.toFixed(1) + ' km from Salgas Junction' : 'Distance not available'));

      var remove = el('button', 'btn btn-sm btn-outline-danger', 'Remove');
      remove.type = 'button';
      remove.dataset.remove = id;
      remove.setAttribute('aria-label', 'Remove ' + place.name + ' from your plan');

      item.appendChild(info);
      item.appendChild(remove);
      list.appendChild(item);
    });
    root.appendChild(list);

    var clearBtn = el('button', 'btn btn-sm btn-link text-danger px-0', 'Clear the whole plan');
    clearBtn.type = 'button';
    clearBtn.dataset.clear = '1';
    root.appendChild(clearBtn);
  }

  // FR-28: Remove one stop, or clear all
  root.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('[data-remove]');
    if (removeBtn) {
      var place = places[removeBtn.dataset.remove];
      DayPlan.remove(removeBtn.dataset.remove);
      DayPlan.notify((place ? place.name : 'The place') + ' was removed from your plan.', 'secondary');
      render();
      return;
    }
    if (e.target.closest('[data-clear]') && window.confirm('Remove all stops from your plan?')) {
      DayPlan.clear();
      DayPlan.notify('Your plan was cleared.', 'secondary');
      render();
    }
  });

  // Load current place details, drop any place that is no longer listed, then draw
  fetch(root.dataset.api, { headers: { Accept: 'application/json' } })
    .then(function (response) {
      if (!response.ok) { throw new Error('HTTP ' + response.status); }
      return response.json();
    })
    .then(function (data) {
      data.places.forEach(function (p) { places[p.id] = p; });

      var plan = DayPlan.load();
      var stillListed = plan.stops.filter(function (id) { return places[id]; });
      if (stillListed.length !== plan.stops.length) {
        plan.stops = stillListed;
        DayPlan.save(plan);
        DayPlan.notify('A place in your plan is no longer listed and was removed.', 'warning');
      }
      render();
    })
    .catch(function () {
      root.textContent = '';
      root.appendChild(el('p', 'text-danger', 'Your plan could not be loaded. Please refresh the page.'));
    });
})();