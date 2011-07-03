<p class="single-media">
<?php __("Date"); ?>
<span class="tooltip-anchor">
<?php echo $html->link($media['Media']['date'], $imageData->getDateLink($media, '3h')); ?>
<span class="tooltip-actions"><span class="sub">
<?php 
  echo $html->link($imageData->getIcon('date_previous', __("View media of previous dates", true)), 
    $imageData->getDateLink($media, 'to'), array('escape' => false)); 
?>
<?php if ($search->getFrom()) : ?>
<?php 
  if (!$search->getTo()) {
    echo $html->link($imageData->getIcon('date_interval', __("View media of interval", true)), 
      $search->getUri(false, array('to' => $media['Media']['date'])), array('escape' => false)); 
  } else {
    echo $html->link($imageData->getIcon('date_interval_add_prev', __("Set new end date of interval", true)), 
      $search->getUri(false, array('to' => $media['Media']['date'])), array('escape' => false)); 
  }
?>
<?php endif; ?>
<?php 
  echo $html->link($imageData->getIcon('calendar_view_day', __("View media of this day", true)), 
    $imageData->getDateLink($media, '12d'), array('escape' => false)); 
  echo $html->link($imageData->getIcon('calendar_view_week', __("View media of this week", true)), 
    $imageData->getDateLink($media, '3.5d'), array('escape' => false)); 
  echo $html->link($imageData->getIcon('calendar_view_month', __("View media of this month", true)), 
    $imageData->getDateLink($media, '15d'), array('escape' => false)); 
?>
<?php if ($search->getTo()) : ?>
<?php 
  if (!$search->getFrom()) {
    echo $html->link($imageData->getIcon('date_interval', __('View media of interval', true)), 
      $search->getUri(false, array('from' => $media['Media']['date'])), array('escape' => false)); 
  } else { 
    echo $html->link($imageData->getIcon('date_interval_add_next', __('Set new start date for interval', true)), 
      $search->getUri(false, array('from' => $media['Media']['date'])), array('escape' => false)); 
  } 
?>
<?php endif; ?>
<?php 
  echo $html->link($imageData->getIcon('date_next', __('View media of next dates', true)), 
    $imageData->getDateLink($media, 'from'), array('escape' => false)); 
?>
</span></span>
</span>
</p>
