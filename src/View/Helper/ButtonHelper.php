<?php

declare(strict_types=1);

namespace App\View\Helper;

use App\Enum\ActionColor;
use App\Utility\FaIcon;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Button helper
 */
class ButtonHelper extends Helper
{
    const ICON_POSITION_LEFT = 'left';
    const ICON_POSITION_RIGHT = 'right';

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'icon' => [
            'link' => 'default',
            'save' => 'save',
            'validate' => 'validate',
            'export' => 'file-csv',
            'search' => 'search',
            'view' => 'view',
            'add' => 'add',
            'edit' => 'edit',
            'delete' => 'delete',
            'back' => 'back',
            'report' => 'report',
        ],
        'icon_class' => 'fa-fw',
        'icon_position' => self::ICON_POSITION_LEFT, // left, right
    ];

    public $helpers = ['Form', 'Html'];

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function link(array $options = []): string
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required');
        }

        if (empty($options['label']) && empty($options['icon'])) {
            $options['icon'] = FaIcon::get($this->getConfig('icon.link'), $this->getConfig('icon_class'));
        }

        if (empty($options['actionColor'])) {
            throw new \InvalidArgumentException('actionColor is required');
        }

        $url = $options['url'];
        unset($options['url']);

        $title = $this->setIconPosition($options['label'] ?? null, $options['icon'] ?? null, $options['icon_position'] ?? null);
        unset($options['label']);
        unset($options['icon']);
        unset($options['icon_position']);

        $actionColor = $options['actionColor'];
        unset($options['actionColor']);

        $outline = $options['outline'] ?? false;
        unset($options['outline']);

        $options = array_merge([
            'escape' => false,
        ], $options);

        if (!empty($options['class']) && $options['override']) {
        } else {
            $options['class'] = $this->prepareClass($options['class'] ?? '', $actionColor, $outline);
        }

        return $this->Html->link($title, $url, $options);
    }

    public function postLink(array $options): string
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required');
        }

        if (empty($options['label']) && empty($options['icon'])) {
            $options['icon'] = FaIcon::get($this->getConfig('icon.link'), $this->getConfig('icon_class'));
        }

        if (empty($options['actionColor'])) {
            throw new \InvalidArgumentException('actionColor is required');
        }

        $url = $options['url'];
        unset($options['url']);

        $title = $this->setIconPosition($options['label'] ?? null, $options['icon'] ?? null, $options['icon_position'] ?? null);
        unset($options['label']);
        unset($options['icon']);
        unset($options['icon_position']);

        $actionColor = $options['actionColor'];
        unset($options['actionColor']);

        $outline = $options['outline'] ?? false;
        unset($options['outline']);

        $options = array_merge([
            'escape' => false,
            'block' => true,
        ], $options);

        if (!empty($options['class']) && $options['override']) {
        } else {
            $options['class'] = $this->prepareClass($options['class'] ?? '', $actionColor, $outline);
        }

        return $this->Form->postLink($title, $url, $options);
    }


    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function submit(array $options = []): string
    {
        if (empty($options['actionColor'])) {
            throw new \InvalidArgumentException('actionColor is required');
        }

        if (empty($options['label']) && empty($options['icon'])) {
            throw new \InvalidArgumentException('label is required');
        }

        $title = $this->setIconPosition($options['label'] ?? null, $options['icon'] ?? null, $options['icon_position'] ?? null);
        unset($options['label']);
        unset($options['icon']);
        unset($options['icon_position']);

        $actionColor = $options['actionColor'];
        unset($options['actionColor']);

        $outline = $options['outline'] ?? false;
        unset($options['outline']);

        $options = array_merge([
            'escapeTitle' => false,
            'type' => 'submit',
        ], $options);

        if (!empty($options['class']) && $options['override']) {
        } else {
            $options['class'] = $this->prepareClass($options['class'] ?? '', $actionColor, $outline);
        }

        return $this->Form->button($title, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function save(array $options = []): string
    {
        $options = array_merge([
            'name' => 'action',
            'value' => 'save',
            'icon' => FaIcon::get($this->getConfig('icon.save'), $this->getConfig('icon_class')),
            'label' => __('Guardar'),
            'actionColor' => ActionColor::SUBMIT,
        ], $options);

        return $this->submit($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function validate(array $options = []): string
    {
        $options = array_merge([
            'name' => 'action',
            'value' => 'validate',
            'icon' => FaIcon::get($this->getConfig('icon.validate'), $this->getConfig('icon_class')),
            'label' => __('Guardar y Validar'),
            'actionColor' => ActionColor::VALIDATE,
            'confirm' => __('Seguro que desea validar este registro?'),
        ], $options);

        return $this->submit($options);
    }

    public function closeModal(array $options = []): string
    {
        $options = array_merge([
            'type' => 'button',
            'data-dismiss' => 'modal',
            'icon' => false,
            'label' => __('Cancelar'),
            'actionColor' => ActionColor::CANCEL,
        ], $options);

        return $this->submit($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function export(array $options = []): string
    {
        $options = array_merge([
            'name' => 'export',
            'value' => 'csv',
            'icon' => FaIcon::get($this->getConfig('icon.export'), $this->getConfig('icon_class')),
            'label' => __('Exportar'),
            'actionColor' => ActionColor::REPORT,
        ], $options);

        return $this->submit($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function search(array $options = []): string
    {
        $options = array_merge([
            'name' => 'action',
            'value' => 'search',
            'icon' => FaIcon::get($this->getConfig('icon.search'), $this->getConfig('icon_class')),
            'label' => __('Buscar'),
            'actionColor' => ActionColor::SEARCH,
        ], $options);

        return $this->submit($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function view(array $options = []): string
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required');
        }

        $options = array_merge([
            'icon' => FaIcon::get($this->getConfig('icon.view'), $this->getConfig('icon_class')),
            'label' => false,
            'escape' => false,
            'actionColor' => ActionColor::VIEW,
            'override' => false,
            'outline' => true,
        ], $options);

        return $this->link($options);
    }

    public function report(array $options = []): string
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required');
        }

        $options = array_merge([
            'icon' => FaIcon::get($this->getConfig('icon.report'), $this->getConfig('icon_class')),
            'label' => false,
            'escape' => false,
            'actionColor' => ActionColor::REPORT,
            'override' => false,
            'outline' => false,
            'target' => '_blank',
        ], $options);

        return $this->link($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function add(array $options = []): string
    {
        $options = array_merge([
            'icon' => FaIcon::get($this->getConfig('icon.add'), $this->getConfig('icon_class')),
            'label' => __('Agregar'),
            'escape' => false,
            'actionColor' => ActionColor::ADD,
            'override' => false,
            'outline' => false,
        ], $options);

        return $this->link($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function edit(array $options = []): string
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required');
        }

        $options = array_merge([
            'icon' => FaIcon::get($this->getConfig('icon.edit'), $this->getConfig('icon_class')),
            'label' => false,
            'escape' => false,
            'actionColor' => ActionColor::EDIT,
            'override' => false,
            'outline' => true,
        ], $options);

        return $this->link($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function delete(array $options = []): string
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required');
        }

        $options = array_merge([
            'icon' => FaIcon::get($this->getConfig('icon.delete'), $this->getConfig('icon_class')),
            'label' => __('Eliminar'),
            'escape' => false,
            'actionColor' => ActionColor::DELETE,
            'override' => false,
            'outline' => false,
            'confirm' => __('Seguro que desea eliminar este registro?'),
        ], $options);

        return $this->postLink($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function remove(array $options = []): string
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required');
        }

        $options = array_merge([
            'icon' => $this->getDefaultIcon(__FUNCTION__),
            'label' => __('Eliminar'),
            'escape' => false,
            'actionColor' => ActionColor::DELETE,
            'override' => false,
            'outline' => false,
            'confirm' => __('Seguro que desea eliminar este registro?'),
        ], $options);

        return $this->postLink($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function cancel(array $options = []): string
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required');
        }

        $options = array_merge([
            'icon' => false,
            'label' => __('Cancelar'),
            'escape' => false,
            'actionColor' => ActionColor::CANCEL,
            'override' => false,
            'outline' => false,
        ], $options);

        return $this->link($options);
    }

    /**
     * @param array<string, mixed> $options
     * @return string
     */
    public function back(array $options = []): string
    {
        return $this->cancel([
            'icon' => FaIcon::get($this->getConfig('icon.back'), $this->getConfig('icon_class')),
            'label' => __('Volver'),
        ]);
    }

    /**
     * @param array|string $class
     * @param ActionColor $actionColor
     * @param boolean $outline
     * @return string
     */
    protected function prepareClass(array|string $class, ActionColor $actionColor, bool $outline = false): string
    {
        if (is_array($class)) {
            $class = trim(implode(' ', $class));
        }

        return $actionColor->btn($class, $outline);
    }

    /**
     * @param string|null $label
     * @param FaIcon|false|null $icon
     * @return string|null
     */
    protected function setIconPosition(?string $label = null, $icon = null, $position = null): ?string
    {
        $position = $position ?? $this->getConfig('icon_position');
        if ($position === self::ICON_POSITION_RIGHT) {
            $title = trim($label . ' ' . $icon);
        } else {
            $title = trim($icon . ' ' . $label);
        }

        return $title;
    }

    protected function getDefaultIcon(string $name): FaIcon
    {
        try {
            $name = $this->getConfig('icon.' . $name, $name);

            return FaIcon::get($name, $this->getConfig('icon_class'));
        } catch (\Throwable $th) {
            return FaIcon::get('default', $this->getConfig('icon_class'));
        }
    }
}
