// Unit tests for the time budget (FR-32 to FR-34). Run from the project folder: node tests/plan-budget.test.js
const assert = require('assert');
const PlanBudget = require('../assets/js/plan-budget.js');

const places = {
  3:  { openingTime: '06:00', closingTime: '18:00', closedDays: [], visitMin: 90 },             // Diyasaru
  4:  { openingTime: '06:00', closingTime: '21:00', closedDays: [], visitMin: 45 },             // Bellanwila
  9:  { openingTime: '08:30', closingTime: '17:00', closedDays: [], visitMin: 180 },            // Dehiwala Zoo
  5:  { openingTime: '09:00', closingTime: '17:00', closedDays: ['Sun'], visitMin: 75 },        // Attidiya
  12: { openingTime: '09:00', closingTime: '18:00', closedDays: ['Mon', 'Poya'], visitMin: 120 } // National Museum
};
const route = { distanceKm: 22.2, legs: [{ durationMin: 16 }, { durationMin: 14 }, { durationMin: 4 }] };
const poya = ['2026-10-25'];
const plan = (start, date, stops = [3, 4, 9], r = route) =>
  PlanBudget.calculate(stops, places, r, start, date, poya);
const clock = PlanBudget.formatClock;

// FR-32: totals and arrival times
let b = plan('08:00', '2026-10-14');
assert.strictEqual(b.travelMin, 34);
assert.strictEqual(b.visitMin, 315);
assert.strictEqual(clock(b.finish), '1:49 pm');
assert.deepStrictEqual(b.stops.map(s => clock(s.arrive)), ['8:16 am', '10:00 am', '10:49 am']);

// FR-33: the 20:00 boundary
assert.strictEqual(plan('14:10', '2026-10-14').late, false);   // finish 7:59 pm
assert.strictEqual(plan('14:15', '2026-10-14').late, true);    // finish 8:04 pm

// FR-34: opening hours
b = plan('05:30', '2026-10-14');
assert.deepStrictEqual(b.stops[0].warnings, ['Arrives before it opens at 6:00 am']);
assert.deepStrictEqual(b.stops[1].warnings, []);

// FR-34: closed days (25 Oct 2026 is a Sunday and Vap Poya; 26 Oct is a Monday)
const r2 = { distanceKm: 30, legs: [{ durationMin: 25 }, { durationMin: 35 }] };
b = plan('09:00', '2026-10-25', [5, 12], r2);
assert.deepStrictEqual(b.stops[0].warnings, ['Closed on Sundays']);
assert.deepStrictEqual(b.stops[1].warnings, ['Closed on Poya days, and 2026-10-25 is a Poya day']);
assert.deepStrictEqual(plan('09:00', '2026-10-26', [5, 12], r2).stops[1].warnings, ['Closed on Mondays']);

console.log('All time budget tests passed.');