// FR-26 to FR-28: the visitor's current day plan, kept in this browser (localStorage)
var DayPlan = (function () {
  'use strict';

  var KEY = 'mattegoda.dayPlan.v1';
  var MAX_STOPS = 6;                              // FR-30: more than this gets a warning
  var TIME_PATTERN = /^([01]\d|2[0-3]):[0-5]\d$/;  // "08:00" … "23:59"
  var DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/;        // "2026-10-15"

  // Today's date in the visitor's own time zone, as YYYY-MM-DD
  function today() {
    var d = new Date();
    return d.getFullYear() + '-' +
           String(d.getMonth() + 1).padStart(2, '0') + '-' +
           String(d.getDate()).padStart(2, '0');
  }

  function emptyPlan() {
    return { stops: [], startTime: '08:00', visitDate: '' };
  }

  // Read the plan. Anything broken or missing gives safe defaults instead of an error.
  function load() {
    var plan = emptyPlan();
    try {
      var data = JSON.parse(localStorage.getItem(KEY));
      if (data && Array.isArray(data.stops)) {
        plan.stops = data.stops.map(Number).filter(function (id) { return id > 0; });
        if (TIME_PATTERN.test(data.startTime)) { plan.startTime = data.startTime; }
        if (DATE_PATTERN.test(data.visitDate)) { plan.visitDate = data.visitDate; }
      }
    } catch (e) {
      // storage blocked (private mode) or corrupted: start with an empty plan
    }
    return plan;
  }

  function save(plan) {
    try {
      localStorage.setItem(KEY, JSON.stringify(plan));
    } catch (e) {
      notify('Your plan could not be saved in this browser.', 'danger');
    }
    updateBadge(plan);
    markButtons(plan);
  }

  function has(id) {
    return load().stops.indexOf(Number(id)) !== -1;
  }

  // FR-26 and FR-27: add to the end; refuse a place that is already there
  function add(id) {
    var plan = load();
    id = Number(id);
    if (plan.stops.indexOf(id) !== -1) {
      return false;
    }
    plan.stops.push(id);
    save(plan);
    return true;
  }

  // FR-28
  function remove(id) {
    var plan = load();
    id = Number(id);
    plan.stops = plan.stops.filter(function (s) { return s !== id; });
    save(plan);
  }

  function clear() {
    var plan = load();
    plan.stops = [];
    save(plan);
  }

  // FR-29: move a stop one place up (step -1) or down (step +1)
  function move(id, step) {
    var plan = load();
    id = Number(id);
    var from = plan.stops.indexOf(id);
    var to = from + step;
    if (from === -1 || to < 0 || to >= plan.stops.length) {
      return false;
    }
    plan.stops[from] = plan.stops[to];      // swap the two neighbours
    plan.stops[to] = id;
    save(plan);
    return true;
  }

  // FR-31: start time, e.g. "07:30"
  function setStartTime(value) {
    if (!TIME_PATTERN.test(value)) {
      return false;
    }
    var plan = load();
    plan.startTime = value;
    save(plan);
    return true;
  }

  // Visit date: today or later (needed for the closed-day check, FR-34)
  function setVisitDate(value) {
    if (!DATE_PATTERN.test(value) || value < today()) {
      return false;
    }
    var plan = load();
    plan.visitDate = value;
    save(plan);
    return true;
  }

  // Number next to "Plan my day" in the navbar
  function updateBadge(plan) {
    document.querySelectorAll('[data-plan-count]').forEach(function (el) {
      el.textContent = plan.stops.length;
      el.hidden = plan.stops.length === 0;
    });
  }

  // Every "Add to plan" button shows whether its place is already in the plan
  function markButtons(plan) {
    document.querySelectorAll('[data-add-to-plan]').forEach(function (btn) {
      var inPlan = plan.stops.indexOf(Number(btn.dataset.addToPlan)) !== -1;
      btn.textContent = inPlan ? '✓ In your plan' : '+ Add to plan';
      btn.classList.toggle('btn-success', inPlan);
      btn.classList.toggle('btn-outline-success', !inPlan);
    });
  }

  // Short message in the bottom-right corner (Bootstrap toast), built with textContent
  function notify(message, type) {
    var area = document.getElementById('toast-area');
    if (!area || typeof bootstrap === 'undefined') {
      return;
    }
    var toast = document.createElement('div');
    toast.className = 'toast align-items-center border-0 text-bg-' + (type || 'success');
    toast.setAttribute('role', 'status');

    var row = document.createElement('div');
    row.className = 'd-flex';
    var body = document.createElement('div');
    body.className = 'toast-body';
    body.textContent = message;
    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close me-2 m-auto';
    close.setAttribute('data-bs-dismiss', 'toast');
    close.setAttribute('aria-label', 'Close');

    row.appendChild(body);
    row.appendChild(close);
    toast.appendChild(row);
    area.appendChild(toast);
    toast.addEventListener('hidden.bs.toast', function () { toast.remove(); });
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 3000 }).show();
  }

  function handleAdd(btn) {
    var name = btn.dataset.name || 'This place';
    if (add(btn.dataset.addToPlan)) {
      notify(name + ' was added to your plan.', 'success');
      var count = load().stops.length;
      if (count > MAX_STOPS) {                                         // FR-30: warn, but keep it
        notify('Your plan now has ' + count + ' stops. More than ' + MAX_STOPS +
               ' stops may not fit into one day.', 'warning');
      }
    } else {
      notify(name + ' is already in your plan.', 'warning');           // FR-27
    }
  }

  // One listener for every "Add to plan" button on every page (event delegation)
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-add-to-plan]');
    if (btn) {
      e.preventDefault();
      handleAdd(btn);
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    var plan = load();
    updateBadge(plan);
    markButtons(plan);
  });

  return {
    load: load, save: save, add: add, remove: remove, clear: clear, has: has,
    move: move, setStartTime: setStartTime, setVisitDate: setVisitDate, today: today,
    notify: notify, handleAdd: handleAdd, MAX_STOPS: MAX_STOPS,
    markButtons: function () { markButtons(load()); }
  };
})();