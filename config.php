<?php

// Horário do salão. Dias: 1 = segunda ... 7 = domingo (formato do date('N'))
// Cada dia pode ter vários blocos (ex.: manhã e tarde, com pausa para almoço).
return [
    'salon_name' => 'Salão Aurora',

    'opening_hours' => [
        1 => [['09:00', '13:00'], ['14:00', '19:00']],
        2 => [['09:00', '13:00'], ['14:00', '19:00']],
        3 => [['09:00', '13:00'], ['14:00', '19:00']],
        4 => [['09:00', '13:00'], ['14:00', '19:00']],
        5 => [['09:00', '13:00'], ['14:00', '19:00']],
        6 => [['09:00', '13:00']],
        7 => [], // fechado
    ],

    // de quanto em quanto tempo aparecem horários disponíveis
    'slot_step_min' => 15,

    // não deixa marcar para daqui a menos de X minutos
    'min_notice_min' => 60,

    // até quantos dias no futuro se pode marcar
    'max_days_ahead' => 60,
];
