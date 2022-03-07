<?php

namespace Modules\Statistic\Services\Document\Aggregation;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Activity\Enums\ActivityLogActions;
use Modules\Activity\Enums\ActivityLogNames;
use Modules\Document\Enums\ModerationParticipants;
use Modules\Statistic\Services\Document\Aggregation\Helpers\TimePeriod;
use Modules\Statistic\Services\Document\Interfaces\AggregatorInterface;

class AverageApprovalTimeAggr extends AbstractAggr
{
    private TimePeriod $_timePeriod;
    private array $_users = []; //cache users

    protected function __construct()
    {
        $query = DB::query()
                ->select('documents.type as doc_type', 'activity_log.*')
                ->from('activity_log')
                ->join('documents', 'documents.id', '=', 'activity_log.subject_id')
                ->where(function ($query) {
                    $query->where('activity_log.description', ActivityLogActions::JURIST_REJECTED)
                        ->orwhere('activity_log.description', ActivityLogActions::JURIST_APPROVED)
                        ->orwhere('activity_log.description', ActivityLogActions::PURCHASE_REJECTED)
                        ->orwhere('activity_log.description', ActivityLogActions::PURCHASE_APPROVED)
                        ->orwhere('activity_log.description', ActivityLogActions::MAIN_PURCHASING_REJECTED)
                        ->orwhere('activity_log.description', ActivityLogActions::MAIN_PURCHASING_APPROVED)
                        ->orwhere('activity_log.description', ActivityLogActions::BOOKKEEPING_REJECTED)
                        ->orwhere('activity_log.description', ActivityLogActions::BOOKKEEPING_APPROVED)
                        ->orwhere('activity_log.description', ActivityLogActions::MANAGER_REJECTED)
                        ->orwhere('activity_log.description', ActivityLogActions::MANAGER_APPROVED);
                })
                ->where('activity_log.log_name', '=', ActivityLogNames::DOCUMENT_MODERATION)
                // ->whereRaw("JSON_EXTRACT(properties, '$.key')") //if only new version
                ->orderByRaw('activity_log.created_at, activity_log.id ASC');

        $this->setQuery($query);

        $this->_timePeriod = $this->initTimePeriod();
    }

    protected function parseItem($answer): array
    {
        $query = DB::query()
                    ->from('activity_log')
                    ->where('subject_type', $answer->subject_type)
                    ->where('subject_id', $answer->subject_id)
                    ->where('log_name', '=', ActivityLogNames::DOCUMENT_MODERATION)
                    ->where('created_at', '<', $answer->created_at)
                    ->orderBy('id', 'DESC');

        match ($answer->description) {
            ActivityLogActions::JURIST_REJECTED,
            ActivityLogActions::JURIST_APPROVED => $query->where('description', ActivityLogActions::SEND_TO_JURIST),
            ActivityLogActions::PURCHASE_REJECTED,
            ActivityLogActions::PURCHASE_APPROVED => $query->where('description', ActivityLogActions::SEND_TO_PURCHASE),
            ActivityLogActions::MAIN_PURCHASING_REJECTED,
            ActivityLogActions::MAIN_PURCHASING_APPROVED => $query->where('description', ActivityLogActions::SEND_TO_MAIN_PURCHASING),
            ActivityLogActions::BOOKKEEPING_REJECTED,
            ActivityLogActions::BOOKKEEPING_APPROVED => $query->where('description', ActivityLogActions::SEND_TO_BOOKKEEPING),
            ActivityLogActions::MANAGER_REJECTED,
            ActivityLogActions::MANAGER_APPROVED => $query->where('description', ActivityLogActions::SEND_TO_MANAGER),
            default => throw new \Exception('Not fund '.$answer->description.' in '.__CLASS__)
        };

        $result = [];

        $answer->properties = json_decode($answer->properties);

        $query->get()->each(function ($sent) use ($answer, &$result) {
            if (!empty($result)) {
                return true;
            }

            $sent->properties = json_decode($sent->properties);

            if ($answer->properties->key ?? 0) {//new version
                $sent_email = $sent->properties->email ?? 0;
                $ans_email = $answer->properties->email ?? 0;

                if ($sent_email and $ans_email and $sent_email === $ans_email) {
                    $result = $this->buildItemResult($sent, $answer, $sent_email);
                }
            } else {//old version
                if ($sent->description === ActivityLogActions::SEND_TO_MANAGER) {//only manager
                    $man_id = $sent->properties->manager_id ?? 0;

                    if ($man_id == $answer->causer_id) {
                        $result = $this->buildItemResult($sent, $answer, (int) $man_id);
                    }
                } else {//else recipients
                    $sent_name = $sent->properties->name ?? 0;
                    $ans_name = $answer->properties->name ?? 0;

                    if ($sent_name and $ans_name and $sent_name === $ans_name) {
                        if ($email = ($sent->properties->email ?? 0)) {
                            $result = $this->buildItemResult($sent, $answer, $email);
                        }
                    }
                }
            }
        });

        return $result;
    }

    public function afterDate(string $date): AggregatorInterface
    {
        $this->getQuery()->where('activity_log.created_at', '>', $date);

        return $this;
    }

    protected function buildItemResult($sent, $answer, int|string $email_or_user_id): array
    {
        $user_id = $email_or_user_id;

        if (is_string($email_or_user_id)) {
            if (isset($this->_users[$email_or_user_id])) {
                $user_id = $this->_users[$email_or_user_id];
            } else {
                $user_id = DB::table('users')->where('email', $email_or_user_id)->value('id');

                if ($user_id) {
                    $this->_users[$email_or_user_id] = $user_id;
                }
            }
        }

        if (!$user_id) {
            return [];
        }

        $department = match ($sent->description) {
            ActivityLogActions::SEND_TO_JURIST => ModerationParticipants::JURIST,
            ActivityLogActions::SEND_TO_PURCHASE,
            ActivityLogActions::SEND_TO_MAIN_PURCHASING => ModerationParticipants::PURCHASE,
            ActivityLogActions::SEND_TO_BOOKKEEPING => ModerationParticipants::BOOKKEEPING,
            ActivityLogActions::SEND_TO_MANAGER => ModerationParticipants::MANAGER,
            default => throw new \Exception('Participant not fund '.$sent->description.' in '.__CLASS__)
        };

        $period = $this->_timePeriod->calculate(new Carbon($sent->created_at), new Carbon($answer->created_at));

        return [
            'doc_id' => $answer->subject_id,
            'type' => $answer->doc_type,
            'user_id' => $user_id,
            'department' => $department,
            'time_human' => $period->getHumanTime(),
            'seconds' => $period->getAllSeconds(),
            'sent_activity_id' => $sent->id,
            'response_activity_id' => $answer->id,
            'sent_time' => $sent->created_at,
            'response_time' => $answer->created_at,
        ];
    }

    protected function initTimePeriod(): TimePeriod
    {
        //исключаем определенные даты или время, иначе всегда возвращаем false
        $exceptFunc = function (Carbon $date) {
            $number = $date->format('N'); //номер дня недели
            $result = ($number == 6 or $number == 7); //суббота или воскресенье

            if (!$result) {
                $result = match ($date->format('m-d')) {//месяц-день
                                '01-01',//новогодние каникулы
                                '01-02',//новогодние каникулы
                                '01-03',//новогодние каникулы
                                '01-04',//новогодние каникулы
                                '01-05',//новогодние каникулы
                                '01-06',//новогодние каникулы
                                '01-07',//Рождество Христово
                                '01-08',//новогодние каникулы
                                '02-23',//день защитника Отечества
                                '03-08',//8 марта
                                '05-01',//Праздник Весны и Труда
                                '05-09',//День Победы
                                '06-12',//День России
                                '11-04' => true,//День народного единства
                                default => false,
                };
            }

            return $result;
        };
        //изменяем на лету раб время, иначе всегда указываем дефолтное время
        $workTimeFunc = function (Carbon $date, int &$hourFrom, int &$hourTo) {
            if ($date->format('N') == 5) {//если пятница, укороченный день с 9 до 17 часов
                $hourFrom = 9;
                $hourTo = 17;
            } else {
                $hourFrom = 9;
                $hourTo = 18;
            }
        };
        $period = new TimePeriod();
        $period->setExceptClosure($exceptFunc);
        $period->setWorkTimeClosure($workTimeFunc);

        return $period;
    }
}
