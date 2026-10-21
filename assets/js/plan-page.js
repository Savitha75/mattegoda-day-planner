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
  var saveBox    = document.getElementById('plan-save');      // FR-35: save / open card
  var saveResult = document.getElementById('save-result');

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
    if (saveResult) { saveResult.textContent = ''; }   // a shown code belongs to the plan as it was saved

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
    var plan = DayPlan.load();
    var box = document.getElementById('plan-budget');
    if (!box) {
      buildPrint(plan, null, null);             // empty plan: nothing to show
      return;
    }
    box.textContent = '';

    var route = PlanRoute.route();
    if (!route) {
      box.appendChild(el('p', 'small text-muted mb-0', PlanRoute.status() === 'failed'
        ? 'Arrival times need the route, which is not available right now.'
        : 'Working out arrival times…'));
      buildPrint(plan, null, null);
      return;
    }

    var b = PlanBudget.calculate(plan.stops, places, route, plan.startTime, plan.visitDate, poyaDates);
    buildPrint(plan, b, route);

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

  // FR-36: the printable summary. It is hidden on screen and is the only thing printed (see style.css).
  function buildPrint(plan, b, route) {
    var box = document.getElementById('print-summary');
    if (!box) { return; }
    box.textContent = '';

    box.appendChild(el('h1', 'h4 mb-1', 'My day plan'));
    box.appendChild(el('p', 'mb-3', 'Visit date: ' + plan.visitDate +
      ' · Start: Salgas Junction, Mattegoda at ' +
      PlanBudget.formatClock(PlanBudget.toMinutes(plan.startTime))));

    if (plan.stops.length === 0) {
      box.appendChild(el('p', '', 'Your plan is empty.'));
      return;
    }

    var table = el('table', 'table table-sm table-bordered align-top');
    var head = el('tr');
    ['#', 'Place', 'Arrive', 'Leave', 'Distance from previous stop', 'Travel tips'].forEach(function (h) {
      head.appendChild(el('th', '', h));
    });
    var thead = el('thead');
    thead.appendChild(head);
    table.appendChild(thead);

    var tbody = el('tbody');
    plan.stops.forEach(function (id, i) {
      var p = places[id];
      if (!p) { return; }
      var s = b ? b.stops[i] : null;
      var leg = route ? route.legs[i] : null;

      var row = el('tr');
      row.appendChild(el('td', '', String(i + 1)));
      var nameCell = el('td', '', p.name);
      if (s) {
        s.warnings.forEach(function (w) { nameCell.appendChild(el('div', 'small fw-semibold', '⚠ ' + w)); });
      }
      row.appendChild(nameCell);
      row.appendChild(el('td', '', s ? PlanBudget.formatClock(s.arrive) : '–'));
      row.appendChild(el('td', '', s ? PlanBudget.formatClock(s.leave) : '–'));
      row.appendChild(el('td', '', leg
        ? leg.distanceKm.toFixed(1) + ' km (' + leg.durationMin + ' min)' + (i === 0 ? ' from Salgas Junction' : '')
        : '–'));
      row.appendChild(el('td', 'small', p.travelTips || 'No tips recorded.'));
      tbody.appendChild(row);
    });
    table.appendChild(tbody);
    box.appendChild(table);

    if (b) {
      box.appendChild(el('p', 'mb-1', 'Total road distance ' + b.distanceKm.toFixed(1) + ' km · travel ' +
        PlanBudget.formatDuration(b.travelMin) + ' · visits ' + PlanBudget.formatDuration(b.visitMin) +
        ' · estimated finish ' + PlanBudget.formatClock(b.finish) + ' (leaving the last stop)'));
      if (b.late) {
        box.appendChild(el('p', 'fw-semibold mb-1', '⚠ The finish is after 8:00 pm. This plan may not be achievable in one day.'));
      }
    } else {
      box.appendChild(el('p', 'mb-1', 'Arrival times and distances were not available because route guidance was unavailable.'));
    }
    // NFR-06: the same advisory notice as on screen
    box.appendChild(el('p', 'small mt-3 mb-0', 'Advisory only. Route guidance is not based on live traffic conditions ' +
      'and must not be used as a live navigation aid while driving. Check opening times before you travel.'));
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

    // FR-32 to FR-34: arrival times, finish and closed-day warnings follow the new date or start time
    showBudget();
  });

  // FR-35: fetch JSON; a failed request becomes an Error with the server's message
  function requestJson(url, options) {
    return fetch(url, options).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok) { throw new Error(data.error || ('HTTP ' + response.status)); }
        return data;
      });
    });
  }

  function showSavedCode(code) {
    saveResult.textContent = '';
    var box = el('div', 'alert alert-success mb-0');
    box.appendChild(el('div', '', 'Plan saved. Your reference code is:'));
    box.appendChild(el('div', 'fs-3 fw-bold font-monospace my-1', code));
    var link = el('a', 'small', 'Link that opens this plan');
    link.href = window.location.pathname + '?code=' + code;
    box.appendChild(link);
    saveResult.appendChild(box);
  }

  // FR-35: open a saved plan by its code
  function openSaved(code) {
    requestJson(saveBox.dataset.api + '?code=' + encodeURIComponent(code), { headers: { Accept: 'application/json' } })
      .then(function (data) {
        if (DayPlan.load().stops.length > 0 &&
            !window.confirm('Replace your current plan with saved plan ' + data.refCode + '?')) {
          return;
        }
        var stops = data.stops.filter(function (id) { return places[id]; });   // drop places no longer listed
        var visitDate = data.visitDate < DayPlan.today() ? DayPlan.today() : data.visitDate;
        DayPlan.save({ stops: stops, startTime: data.startTime, visitDate: visitDate });
        render();
        DayPlan.notify('Saved plan ' + data.refCode + ' opened.', 'success');
        if (stops.length < data.stops.length) {
          DayPlan.notify('A place in the saved plan is no longer listed and was left out.', 'warning');
        }
        if (visitDate !== data.visitDate) {
          DayPlan.notify('The saved visit date has passed, so today is used instead.', 'secondary');
        }
      })
      .catch(function (err) {
        DayPlan.notify(err.message, 'warning');
      });
  }

  if (saveBox) {
    // FR-35: save
    document.getElementById('save-plan').addEventListener('click', function () {
      var btn = this;
      var plan = DayPlan.load();
      if (plan.stops.length === 0) {
        DayPlan.notify('Add at least one place before saving.', 'warning');
        return;
      }
      btn.disabled = true;
      requestJson(saveBox.dataset.api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ stops: plan.stops, startTime: plan.startTime, visitDate: plan.visitDate })
      })
        .then(function (data) { showSavedCode(data.refCode); })
        .catch(function (err) { DayPlan.notify('The plan could not be saved. ' + err.message, 'danger'); })
        .finally(function () { btn.disabled = false; });
    });

    // FR-35: open by code
    document.getElementById('load-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var code = document.getElementById('load-code').value.trim().toUpperCase();
      if (!/^[A-Z0-9]{8}$/.test(code)) {
        DayPlan.notify('A reference code has 8 letters and numbers.', 'warning');
        return;
      }
      openSaved(code);
    });

    // FR-36: print
    document.getElementById('print-plan').addEventListener('click', function () {
      if (DayPlan.load().stops.length === 0) {
        DayPlan.notify('Add at least one place before printing.', 'warning');
        return;
      }
      window.print();
    });
  }

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

      // FR-35: plan.php?code=K7M3QX9P opens that saved plan
      var urlCode = new URLSearchParams(window.location.search).get('code');
      if (urlCode && saveBox) {
        openSaved(urlCode.trim().toUpperCase());
      }
    })
    .catch(function () {
      root.textContent = '';
      root.appendChild(el('p', 'text-danger', 'Your plan could not be loaded. Please refresh the page.'));
    });
})();