<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Field\Stages;
use Cake\View\Helper;
use Cake\View\View;

/**
 * App helper
 */
class AppHelper extends Helper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [];


    public function statusIcon($status = null, $complete = false, $extraClass = '')
    {
        switch ($status) {
            case Stages::STATUS_IN_PROGRESS: 
                $icon = 'fas fa-cogs';
                break;
            case Stages::STATUS_WAITING: 
                $icon = 'fas fa-pause-circle';
                break;
            case Stages::STATUS_SUCCESS: 
                $icon = 'fas fa-check';
                break;
            case Stages::STATUS_PENDING:
            default:
                $icon = 'fas fa-lock';
                break;
        }

        if (empty($complete)) {
            return $icon;
        }

        return '<i class="' . implode(' ', [
            $icon,
            $extraClass,
        ]) . '"></i>';
    }

    public function statusColor($status = null, $prefix = 'card')
    {
        switch ($status) {
            case Stages::STATUS_IN_PROGRESS: 
                $color = 'warning';
                break;
            case Stages::STATUS_WAITING: 
                $color = 'info';
                break;
            case Stages::STATUS_SUCCESS: 
                $color = 'success';
                break;
            case Stages::STATUS_FAILED:
                $color = 'danger';
                break;
            case Stages::STATUS_PENDING:
            default:
                $color = 'gray';
                break;
        }

        if (empty($prefix)) {
            return $color;
        }

        return $prefix . '-' . $color;
    }

    public function statusActive($status = null, $active = 'show', $inactive = '')
    {
        switch($status) {
            case Stages::STATUS_IN_PROGRESS:
            case Stages::STATUS_WAITING:
                return $active;
                break;

            default:
                return $inactive;
                break;
        }
    }
}