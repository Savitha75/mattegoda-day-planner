// FR-32 to FR-34: the time budget for the day plan (calculation only; it does not touch the page)
var PlanBudget = (function () {
  'use strict';

  var LATEST_FINISH = 20 * 60;   // FR-33: 20:00, as minutes after midnight
  var DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  var FULL_NAMES = { Sun: 'Sundays', Mon: 'Mondays', Tue: 'Tuesdays', Wed: 'Wednesdays',
                     Thu: 'Thursdays', Fri: 'Fridays', Sat: 'Saturdays' };

  // "08:30" -> 510 (minutes after midnight); anything else -> null
  function toMinutes(hhmm) {
    if (typeof hhmm !== 'string' || !/^\d{2}:\d{2}$/.test(hhmm)) {
      return null;
    }
    return Number(hhmm.slice(0, 2)) * 60 + Number(hhmm.slice(3, 5));
  }

  // 510 -> "8:30 am"; 1500 -> "1:00 am (next day)"
  function formatClock(min) {
    var nextDay = min >= 1440;
    min = min % 1440;
    var h = Math.floor(min / 60);
    var m = min % 60;
    var h12 = h % 12 === 0 ? 12 : h % 12;
    return h12 + ':' + String(m).padStart(2, '0') + (h < 12 ? ' am' : ' pm') + (nextDay ? ' (next day)' : '');
  }

  // 135 -> "2 h 15 min"
  function formatDuration(min) {
    var h = Math.floor(min / 60);
    var m = min % 60;
    if (h > 0 && m === 0) { return h + ' h'; }
    return (h > 0 ? h + ' h ' : '') + m + ' min';
  }

  // "2026-10-25" -> "Sun" (built from the parts, so no time-zone shift)
  function weekday(isoDate) {
    var p = isoDate.split('-').map(Number);
    return DAY_NAMES[new Date(p[0], p[1] - 1, p[2]).getDay()];
  }

  /**
   * stops      place IDs in plan order
   * places     id -> { openingTime, closingTime, closedDays, visitMin } (from api/places.php)
   * route      answer of api/route.php: one leg per stop, leg 0 = Salgas Junction -> stop 1
   * startTime  "08:00"; visitDate "2026-10-25"; poyaDates ["2026-10-25", ...]
   */
  function calculate(stops, places, route, startTime, visitDate, poyaDates) {
    var day = weekday(visitDate);
    var isPoya = poyaDates.indexOf(visitDate) !== -1;
    var t = toMinutes(startTime);
    var travelTotal = 0;
    var visitTotal = 0;

    var rows = stops.map(function (id, i) {
      var p = places[id];
      var leg = route.legs[i];
      t += leg.durationMin;                 // drive to this stop
      travelTotal += leg.durationMin;
      var arrive = t;
      var stay = p.visitMin || 0;
      var warnings = [];

      // FR-34: closed on the chosen day
      if (p.closedDays.indexOf(day) !== -1) {
        warnings.push('Closed on ' + FULL_NAMES[day]);
      }
      if (isPoya && p.closedDays.indexOf('Poya') !== -1) {
        warnings.push('Closed on Poya days, and ' + visitDate + ' is a Poya day');
      }

      // FR-34: arrival outside the opening hours (places open 00:00 to 23:59 never warn)
      var open = toMinutes(p.openingTime);
      var close = toMinutes(p.closingTime);
      var allDay = open === 0 && close >= 1439;
      if (open !== null && close !== null && !allDay) {
        if (arrive < open) {
          warnings.push('Arrives before it opens at ' + formatClock(open));
        } else if (arrive >= close) {
          warnings.push('Arrives after it closes at ' + formatClock(close));
        } else if (arrive + stay > close) {
          warnings.push('Closes at ' + formatClock(close) + ', before your ' + formatDuration(stay) + ' visit ends');
        }
      }

      t += stay;                            // time spent at this stop
      visitTotal += stay;
      return { placeId: id, arrive: arrive, stay: stay, leave: t, warnings: warnings };
    });

    return {
      stops: rows,
      distanceKm: route.distanceKm,
      travelMin: travelTotal,
      visitMin: visitTotal,
      finish: t,                            // when the visitor leaves the last stop
      late: t > LATEST_FINISH               // FR-33
    };
  }

  return { calculate: calculate, formatClock: formatClock, formatDuration: formatDuration, toMinutes: toMinutes };
})();

// Lets the same file be tested with Node.js (ignored in the browser)
if (typeof module !== 'undefined') { module.exports = PlanBudget; }