import { strict as assert } from 'node:assert';
import { test } from 'node:test';
import { formatAppointmentTime } from './appointment-time.ts';

test('formats appointment time in the clinic timezone', () => {
    const formatted = formatAppointmentTime('2026-11-02T07:00:00.000Z');

    assert.match(formatted, /10:00/);
});
