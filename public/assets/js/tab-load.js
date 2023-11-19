function load_tab(tabId = null, data_options = null, tabIndex = -1, tabHash = null) {
    if (!tabId) {
        tabId = $('[data-loadable]').attr('id');
    }
    const active_link_class = 'active-li';
    data_options = data_options || {};
   

    if ($(`#${tabId}`).length == 0) {
        alert('L\'élément avec l\'ID '+ tabId + ' n\'existe pas');
        return;
    }
   
    const keyBase = tabId.replace('-', '_');
    const indexKey = `${keyBase}_current_index`;
    const hashKey = `ufr_${keyBase}_current_hash`;
    const urlKey = `ufr_${keyBase}_current_url`;

    const currentIndex = localStorage.getItem(indexKey);
    
    function load_content(url, hash, method = 'GET') {
        localStorage.setItem(hashKey, hash);
        localStorage.setItem(urlKey, url);

        console.log(hash);
       
        $.ajax({
            url: url,
            cache: false,
            //method: method || 'GET',
            beforeSend: function () {
                console.log(tabId);
                $('.tab-pane', '#' + tabId).empty().html('');
                const $hash = $(`#${hash}`);
                if (!$hash.hasClass('active')) {
                    $hash.addClass('active show');
                }
                $(`#${hash}`).html(`
                    <div class="d-flex flex-row justify-content-center tab-loader-text">
                        <div class="p-2">
                            <div class="spinner spinner-primary  spinner-track spinner-lg"></div> 
                        </div>
                        <div class="p-2">Chargement des données</div>
                        
                    </div>
                    
                      
                    
                `);
            },
            success: function (content) {
                $(`#${hash}`).empty().html(content);
            },
            error: function () {
               $(`#${hash}`).empty().html(`
                <div class="alert alert-danger d-flex align-items-center p-5 mb-10" role="alert">
                    <i class="bi bi-shield-exclamation me-3"></i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-danger">Erreur interne du servenue</h4>
                        <span>Une erreur est survenue lors du chargement du contenu de cet onglet.</span>
                    </div>
              
                    </div>
               `);
           }
        });
    }
    const hash_url = document.location.hash.slice(1);
    const hash =  hash_url || localStorage.getItem(hashKey);

   
    const url = localStorage.getItem(urlKey);

    if (data_options.id || tabIndex >= 0 || (hash && $('[href="#'+ hash +'"]').length && (hash_url || $('[href="#'+ hash +'"]').data('href') == url))) {
        const $active_tab_link = $('[href="#'+ hash +'"]');
       
        if (tabIndex >= 0 || tabHash || data_options.id) {
            let active_url, $active_tab_link, hash;
            if (data_options.id) {
                
                $(`#${tabId} ${data_options.id} a`).tab('show'); // Select third tab (0-indexed)
                $active_tab_link = $(`#${tabId} a.active`);
                [, hash] = $active_tab_link.attr('href').split('#');
                active_url = $active_tab_link.data('href');
            } else if (tabIndex >= 0) {
                $(`#${tabId} li:eq(${tabIndex}) a`).tab('show'); // Select third tab (0-indexed)
                $active_tab_link = $(`#${tabId} a.active`);
                [, hash] = $active_tab_link.attr('href').split('#');
                active_url = $active_tab_link.data('href');
            } else {
                $active_tab_link = $('[href="'+tabHash+'"]');
                $active_tab_link.tab('show');
                active_url = $active_tab_link.data('href');
            }
            
            $active_tab_link.closest('li').addClass(active_link_class);
            load_content(active_url, hash, $active_tab_link.data('method'));
        } else {
            if ($active_tab_link.length) {
                const $li_parent = $active_tab_link.closest('li');
                $li_parent.addClass(active_link_class);
           //console.log($active_tab_link);
   
               $active_tab_link.tab('show');
               load_content(hash_url  ? $('[href="#'+ hash +'"]').data('href') : url ,  hash, $('[href="#'+ hash +'"]').data('method'));
           }
          
        }
        

    }  else {
       
       $(`#${tabId} li:eq(0) a`).tab('show'); // Select third tab (0-indexed)
        const $active_tab_link = $(`#${tabId} a.active`);
        $active_tab_link.closest('li').addClass(active_link_class);
       
        const [, hash] = $active_tab_link.attr('href').split('#');
        const active_url = $active_tab_link.data('href');

        load_content(active_url, hash, $active_tab_link.data('method'));
    }
    $(document)
    .on('click', '.nav-tab-links a', (e) => e.stopImmediatePropagation())
    .on('shown.bs.tab', `#${tabId}`, function (e) {
      
        e.stopImmediatePropagation();
        const target = e.target;
        //const previousTarget = e.relatedTarget;
        const $target = $(target);
        const [, hash] = target.href.split('#');
          const previousTarget = e.relatedTarget;

        if (previousTarget) {
            const [, oldHash] = previousTarget.href.split('#');
             $('#' + oldHash).empty().html('');
        }


        $(`#${tabId}`).find('.nav-item').removeClass(active_link_class);

        console.log($target, hash);

        $target.closest('li').addClass(active_link_class);


        localStorage.setItem(indexKey, $target.closest('li').index());
        //localStorage.setItem('old_denombrement_type', $target.data('type'));

        load_content($target.data('href'), hash, $target.data('method'));
        
    });
}
    