<?php

namespace Devletes\TimePicker\Tests\Unit;

use Devletes\TimePicker\Forms\Components\TimePicker;
use Devletes\TimePicker\Tests\TestCase;

/**
 * The geometry of the face. It is drawn server-side, so where each numeral
 * lands is a fact this package owns rather than something the browser works
 * out — and the angle a numeral sits at is the same arithmetic the pointer
 * uses in reverse, so getting it wrong puts the hand on the wrong number.
 */
class ClockFaceTest extends TestCase
{
    public function test_the_twelve_hour_face_puts_twelve_at_the_top(): void
    {
        $rings = TimePicker::make('t')->hoursCycle(12)->getClockFace()['hour'];

        $this->assertCount(1, $rings, 'A 12-hour face has a single ring.');

        $values = array_column($rings[0], 'value');

        $this->assertSame([12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], $values);

        // Straight up from the centre of the 224-unit box, on the outer ring.
        $this->assertSame(112.0, $rings[0][0]['x']);
        $this->assertSame(28.0, $rings[0][0]['y']);
    }

    public function test_the_twenty_four_hour_face_puts_midnight_on_an_inner_ring(): void
    {
        $rings = TimePicker::make('t')->hoursCycle(24)->getClockFace()['hour'];

        $this->assertCount(2, $rings);
        $this->assertSame([12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], array_column($rings[0], 'value'));
        $this->assertSame([0, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23], array_column($rings[1], 'value'));

        $this->assertTrue($rings[0][0]['outer']);
        $this->assertFalse($rings[1][0]['outer']);

        // Both rings start at the top; only the radius separates them.
        $this->assertSame(112.0, $rings[1][0]['x']);
        $this->assertSame(58.0, $rings[1][0]['y']);

        $this->assertSame('00', $rings[1][0]['label'], 'Inner numerals are padded.');
    }

    public function test_three_oclock_sits_on_the_right_of_the_face(): void
    {
        $ring = TimePicker::make('t')->hoursCycle(12)->getClockFace()['hour'][0];

        $three = collect($ring)->firstWhere('value', 3);

        $this->assertSame(196.0, $three['x']);
        $this->assertSame(112.0, $three['y']);
    }

    public function test_the_minute_face_is_labelled_every_five(): void
    {
        $rings = TimePicker::make('t')->getClockFace()['minute'];

        $this->assertCount(1, $rings);
        $this->assertSame(
            [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55],
            array_column($rings[0], 'value'),
        );
        $this->assertSame(['00', '05', '10'], array_slice(array_column($rings[0], 'label'), 0, 3));
    }

    public function test_the_second_face_matches_the_minute_face(): void
    {
        $face = TimePicker::make('t')->getClockFace();

        $this->assertEquals($face['minute'], $face['second']);
    }
}
