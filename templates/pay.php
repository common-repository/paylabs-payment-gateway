<?php get_header(); ?>

<?= the_content() ?>

<div><br></div>
<script>
window.setTimeout( function() {
  window.location.reload();
}, 5000);
</script>
<?php get_footer(); ?>