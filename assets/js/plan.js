// FR-26 to FR-28: the visitor's current day plan, kept in this browser (localStorage)
var DayPlan = (function () {
  'use strict';

  var KEY = 'mattegoda.dayPlan.v1';

  function emptyPlan() {
    return { stops: [], startTime: '08:00', visitDate: '' };
  }

  // Read the plan. Anything broken or missing gives an empty plan instead of an error.
  function load() {
    try {
      var data = JSON.parse(localStorage.getItem(KEY));
      if (data && Array.isArray(data.stops)) {
        data.stops = data.stops.map(Number).filter(function (id) { return id > 0; });
        return Object.assign(emptyPlan(), data);
      }
    } catch (e) {
      // storage blocked (private mode) or corrupted: start with an empty plan
    }
    return emptyPlan();
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
    } else {
      notify(name + ' is already in your plan.', 'warning');      // FR-27
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
    notify: notify, handleAdd: handleAdd,
    markButtons: function () { markButtons(load()); }
  };
})();