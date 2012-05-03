<p class="single-media">
<?php echo __("Date"); ?>
<span class="tooltip-anchor">
<?php echo $this->Html->link($media['Media']['date'], $this->ImageData->getDateLink($media, '3h')); ?>
<span class="tooltip-actions"><span class="sub">
<?php
  echo $this->Html->link($this->ImageData->getIcon('date_previous', __("View media of previous dates")),
    $this->ImageData->getDateLink($media, 'to'), array('escape' => false));
?>
<?php if ($this->Search->getFrom()) : ?>
<?php
  if (!$this->Search->getTo()) {
    echo $this->Html->link($this->ImageData->getIcon('date_interval', __("View media of interval")),
      $this->Search->getUri(false, array('to' => $media['Media']['date'])), array('escape' => false));
  } else {
    echo $this->Html->link($this->ImageData->getIcon('date_interval_add_prev', __("Set new end date of interval")),
      $this->Search->getUri(false, array('to' => $media['Media']['date'])), array('escape' => false));
  }
?>
<?php endif; ?>
<?php
  echo $this->Html->link($this->ImageData->getIcon('calendar_view_day', __("View media of this day")),
    $this->ImageData->getDateLink($media, '12h'), array('escape' => false));
  echo $this->Html->link($this->ImageData->getIcon('calendar_view_week', __("View media of this week")),
    $this->ImageData->getDateLink($media, '3.5d'), array('escape' => false));
  echo $this->Html->link($this->ImageData->getIcon('calendar_view_month', __("View media of this month")),
    $this->ImageData->getDateLink($media, '15d'), array('escape' => false));
?>
<?php if ($this->Search->getTo()) : ?>
<?php
  if (!$this->Search->getFrom()) {
    echo $this->Html->link($this->ImageData->getIcon('date_interval', __('View media of interval')),
      $this->Search->getUri(false, array('from' => $media['Media']['date'])), array('escape' => false));
  } else {
    echo $this->Html->link($this->ImageData->getIcon('date_interval_add_next', __('Set new start date for interval')),
      $this->Search->getUri(false, array('from' => $media['Media']['date'])), array('escape' => false));
  }
?>
<?php endif; ?>
<?php
  echo $this->Html->link($this->ImageData->getIcon('date_next', __('View media of next dates')),
    $this->ImageData->getDateLink($media, 'from'), array('escape' => false));
?>
</span></span>
</span>
</p>
