// FR-28 to FR-31: show the plan, reorder and remove stops, set the visit date and start time
(function () {
  'use strict';

  var root = document.getElementById('plan-app');
  if (!root || typeof DayPlan === 'undefined') {
    return;
  }

  var places = {};          // place id -> place data from the API
  var focusAfter = null;    // FR-29: keep keyboard focus on a stop after it moves
  var poyaDates = [];       // FR-34: Poya days from config.php, e.g. ["2026-10-25", ...]
  try { poyaDates = JSON.parse(root.dataset.poya || '[]'); } catch (e) { poyaDates = []; }

  function el(tag, className, text) {
    var e = document.createElement(tag);
    if (className) { e.className = className; }
    if (text !== undefined) { e.textContent = text; }
    return e;
  }

  // FR-31 + visit date: the settings panel
  function settingsPanel(plan) {
    var card = el('div', 'card card-body shadow-sm mb-3');
    var row = el('div', 'row g-3');

    var dateCol = el('div', 'col-sm-6');
    var dateLabel = el('label', 'form-label small mb-1', 'Visit date');
    dateLabel.htmlFor = 'visit-date';
    var date = el('input', 'form-control');
    date.type = 'date';
    date.id = 'visit-date';
    date.min = DayPlan.today();
    date.value = plan.visitDate;
    date.dataset.setting = 'date';
    dateCol.appendChild(dateLabel);
    dateCol.appendChild(date);

    var timeCol = el('div', 'col-sm-6');
    var timeLabel = el('label', 'form-label small mb-1', 'Start time');
    timeLabel.htmlFor = 'start-time';
    var time = el('input', 'form-control');
    time.type = 'time';
    time.id = 'start-time';
    time.step = 300;                       // 5-minute steps
    time.value = plan.startTime;
    time.dataset.setting = 'time';
    timeCol.appendChild(timeLabel);
    timeCol.appendChild(time);
    timeCol.appendChild(el('div', 'form-text', 'The day starts from Salgas Junction at this time.'));

    row.appendChild(dateCol);
    row.appendChild(timeCol);
    card.appendChild(row);
    return card;
  }

  function render() {
    var plan = DayPlan.load();
    root.textContent = '';

    // FR-23: redraw the route whenever the stops change (also clears it for an empty plan)
    PlanRoute.update(plan.stops, places, showBudget);

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

    root.appendChild(settingsPanel(plan));

    var n = plan.stops.length;

    // FR-30: warn when there are more than six stops (the stops are still kept)
    if (n > DayPlan.MAX_STOPS) {
      var warn = el('div', 'alert alert-warning',
        'Your plan has ' + n + ' stops. More than ' + DayPlan.MAX_STOPS +
        ' stops may not fit into one day. Consider removing some.');
      warn.setAttribute('role', 'alert');
      root.appendChild(warn);
    }

    // FR-32, FR-33: the day summary is filled in by showBudget()
    var budget = el('div', 'mb-3');
    budget.id = 'plan-budget';
    root.appendChild(budget);

    root.appendChild(el('p', 'mb-2', n +(n === 1 ? ' stop' : ' stops') + ' in your plan'));

    var list = el('ol', 'list-group list-group-numbered mb-3');
    plan.stops.forEach(function (id, index) {
      var place = places[id];
      if (!place) { return; }

      var item = el('li', 'list-group-item d-flex justify-content-between align-items-start gap-2');
      var info = el('div', 'ms-2 me-auto');
      var link = el('a', 'fw-semibold text-decoration-none', place.name);
      link.href = place.url;
      info.appendChild(link);
      info.appendChild(el('div', 'small text-muted',
        place.distanceKm !== null ? place.distanceKm.toFixed(1) + ' km from Salgas Junction' : 'Distance not available'));

      // FR-32, FR-34: arrival time and warnings for this stop (filled in by showBudget)
      var times = el('div', 'small mt-1');
      times.dataset.stopInfo = id;
      info.appendChild(times);

      // FR-29: up / down buttons (real <button>s, so Tab + Enter works)
      var actions = el('div', 'btn-group btn-group-sm');
      actions.setAttribute('role', 'group');
      actions.setAttribute('aria-label', 'Actions for ' + place.name);

      var up = el('button', 'btn btn-outline-secondary', '↑');
      up.type = 'button';
      up.dataset.move = 'up';
      up.dataset.id = id;
      up.disabled = index === 0;
      up.setAttribute('aria-label', 'Move ' + place.name + ' up');

      var down = el('button', 'btn btn-outline-secondary', '↓');
      down.type = 'button';
      down.dataset.move = 'down';
      down.dataset.id = id;
      down.disabled = index === n - 1;
      down.setAttribute('aria-label', 'Move ' + place.name + ' down');

      var remove = el('button', 'btn btn-outline-danger', 'Remove');
      remove.type = 'button';
      remove.dataset.remove = id;
      remove.setAttribute('aria-label', 'Remove ' + place.name + ' from your plan');

      actions.appendChild(up);
      actions.appendChild(down);
      actions.appendChild(remove);
      item.appendChild(info);
      item.appendChild(actions);
      list.appendChild(item);
    });
    root.appendChild(list);

    var clearBtn = el('button', 'btn btn-sm btn-link text-danger px-0', 'Clear the whole plan');
    clearBtn.type = 'button';
    clearBtn.dataset.clear = '1';
    root.appendChild(clearBtn);

    // FR-29: put keyboard focus back on the stop that just moved
    if (focusAfter) {
      var btn = root.querySelector('[data-id="' + focusAfter.id + '"][data-move="' + focusAfter.dir + '"]');
      if (!btn || btn.disabled) {
        btn = root.querySelector('[data-id="' + focusAfter.id + '"][data-move]:not([disabled])');
      }
      if (btn) { btn.focus(); }
      focusAfter = null;
    }

    showBudget();   // uses the route already loaded, if the stops did not change
  }

  // FR-32 to FR-34: the day summary, the late-finish warning and each stop's times
  function showBudget() {
    var box = document.getElementById('plan-budget');
    if (!box) {
      return;                                   // empty plan: nothing to show
    }
    box.textContent = '';

    var route = PlanRoute.route();
    if (!route) {
      box.appendChild(el('p', 'small text-muted mb-0', PlanRoute.status() === 'failed'
        ? 'Arrival times need the route, which is not available right now.'
        : 'Working out arrival times…'));
      return;
    }

    var plan = DayPlan.load();
    var b = PlanBudget.calculate(plan.stops, places, route, plan.startTime, plan.visitDate, poyaDates);

    // FR-32: the four totals
    var card = el('div', 'card card-body shadow-sm');
    card.appendChild(el('h2', 'h6 mb-2', 'Day summary'));
    var row = el('div', 'row row-cols-2 row-cols-md-4 g-2 text-center');
    [
      ['Road distance', b.distanceKm.toFixed(1) + ' km'],
      ['Travel time', PlanBudget.formatDuration(b.travelMin)],
      ['Visit time', PlanBudget.formatDuration(b.visitMin)],
      ['Estimated finish', PlanBudget.formatClock(b.finish)]
    ].forEach(function (item) {
      var col = el('div', 'col');
      col.appendChild(el('div', 'small text-muted', item[0]));
      col.appendChild(el('div', 'fw-semibold', item[1]));
      row.appendChild(col);
    });
    card.appendChild(row);
    card.appendChild(el('p', 'small text-muted mt-2 mb-0',
      'Finish is when you leave the last stop. The journey home is not included.'));
    box.appendChild(card);

    // FR-33: finishing after 20:00
    if (b.late) {
      var late = el('div', 'alert alert-warning mt-2 mb-0',
        'Estimated finish ' + PlanBudget.formatClock(b.finish) +
        ' is after 8:00 pm. This plan may not be achievable in one day.');
      late.setAttribute('role', 'alert');
      box.appendChild(late);
    }

    // FR-32, FR-34: times and warnings under each stop
    b.stops.forEach(function (s) {
      var slot = root.querySelector('[data-stop-info="' + s.placeId + '"]');
      if (!slot) { return; }
      slot.textContent = '';
      slot.appendChild(el('div', '', 'Arrive ' + PlanBudget.formatClock(s.arrive) +
        ' · stay ' + PlanBudget.formatDuration(s.stay) + ' · leave ' + PlanBudget.formatClock(s.leave)));
      s.warnings.forEach(function (w) {
        slot.appendChild(el('div', 'text-danger fw-semibold', '⚠ ' + w));
      });
    });
  }

  // Clicks: move, remove, clear
  root.addEventListener('click', function (e) {
    var moveBtn = e.target.closest('[data-move]');
    if (moveBtn) {
      var dir = moveBtn.dataset.move;
      if (DayPlan.move(moveBtn.dataset.id, dir === 'up' ? -1 : 1)) {
        focusAfter = { id: moveBtn.dataset.id, dir: dir };
        render();
      }
      return;
    }

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

  // Changes: visit date and start time (FR-31)
  root.addEventListener('change', function (e) {
    var input = e.target;

    if (input.dataset.setting === 'date') {
      if (DayPlan.setVisitDate(input.value)) {
        DayPlan.notify('Visit date set to ' + input.value + '.', 'secondary');
      } else {
        DayPlan.notify('Please choose today or a later date.', 'warning');
        input.value = DayPlan.load().visitDate;
      }
    }

    if (input.dataset.setting === 'time') {
      if (DayPlan.setStartTime(input.value)) {
        DayPlan.notify('Start time set to ' + input.value + '.', 'secondary');
      } else {
        DayPlan.notify('Please enter a valid start time.', 'warning');
        input.value = DayPlan.load().startTime;
      }
    }
  });

  // Load current place details, tidy the plan, then draw
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

      // No date yet, or an old date from a previous day: use today
      if (plan.visitDate === '' || plan.visitDate < DayPlan.today()) {
        DayPlan.setVisitDate(DayPlan.today());
      }
      render();
    })
    .catch(function () {
      root.textContent = '';
      root.appendChild(el('p', 'text-danger', 'Your plan could not be loaded. Please refresh the page.'));
    });
})();