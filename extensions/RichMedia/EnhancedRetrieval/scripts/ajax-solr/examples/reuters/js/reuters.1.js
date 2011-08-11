var Manager;

(function ($) {

  $(function () {
    Manager = new AjaxSolr.Manager({
      solrUrl: 'http://example.solrstuff.org/solrjs/'
    });
    Manager.init();
    Manager.store.addByValue('q', '*:*');
    Manager.doRequest();
  });

})(jQuery);
