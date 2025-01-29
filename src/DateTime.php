<?php

namespace PurpleDot;

use Michalmanko\Holiday\HolidayFactory;

class DateTime extends \DateTime
{
    private const PATTERN = '/([-+]\s?\d+)\s+business\s+day(s)?/i';

    public function __construct($datetime = "now", $datetimezone = null)
    {
        if (preg_match(self::PATTERN, $datetime, $matches)) {
            $businessDaysModifier = $this->getBusinessDaysModifier($matches[1]);
            $remainingTime = preg_replace(self::PATTERN, '', $datetime);
            parent::__construct("now", $datetimezone);

            $this->businessDayProcessor($businessDaysModifier['days'], $businessDaysModifier['direction']);

            if (!empty(trim($remainingTime))) {
                $this->modify(trim($remainingTime));
            }
        } else {
            parent::__construct($datetime, $datetimezone);
        }
    }

    public function modify($modifier)
    {
        if (preg_match(self::PATTERN, $modifier, $matches)) {
            $businessDaysModifier = $this->getBusinessDaysModifier($matches[1]);
            $remainingModifier = preg_replace(self::PATTERN, '', $modifier);
            parent::modify("now");

            $this->businessDayProcessor($businessDaysModifier['businessDays'], $businessDaysModifier['direction'], false);

            if (!empty(trim($remainingModifier))) {
                parent::modify(trim($remainingModifier));
            }

            return $this;
        }

        return parent::modify($modifier);
    }

    private function businessDayProcessor(int $businessDays, int $direction, ?bool $constructor = true)
    {
        $provider = HolidayFactory::createProvider('PL');

        while ($businessDays > 0) {
            if ($constructor) {
                $this->modify($direction > 0 ? '+1 day' : '-1 day');
            } else {
                parent::modify(($direction > 0 ? '+1 day' : '-1 day'));
            }

            $dayOfWeek = $this->format('N');
            if ($dayOfWeek < 6 && !$provider->isHoliday($this)) {
                $businessDays--;
            }
        }
    }

    private function getBusinessDaysModifier(string $businessDaysString)
    {
        $businessDays = (int)preg_replace('/\s/', '', $businessDaysString);
        $direction = $businessDays > 0 ? 1 : -1;

        return [
            'days' => abs($businessDays),
            'direction' => $direction,
        ];
    }
}
