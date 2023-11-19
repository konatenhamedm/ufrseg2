// Charger le contenu de d'un nav-bar à partir de l'url contenue dans celle-ci
const default_template = `
    <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel"</h5>
        <div class="btn btn-icon btn-sm  ms-2" data-bs-dismiss="modal" aria-label="Close">
            <span class="svg-icon svg-icon-2x text-white">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"></rect>
                    <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"></rect>
                </svg>
            </span>
        </div>
    </div>
    <div class="modal-body text-center">
        <div class="spinner spinner-track spinner-primary mr-15">&nbsp;&nbsp;Chargement des données...</div>
        <span></span>
    </div>
`;

const array_sum =  (values) => values.reduce((accumulator, current) => accumulator + current);

$('body').on('click', ".btn-stack", function (e) {
    
    e.preventDefault();
    e.stopImmediatePropagation();
    const $this = $(this);
    const $container = $this.closest('.modal-body');
    const $modal_content = $container.closest('.modal-content');
  
    const $modal_dialog = $container.closest('.modal-dialog');
    const target = $this.attr('data-bs-stacked-modal');

    $.ajax({
      url: $this.attr('href'),
      beforeSend: function () {
        $modal_dialog.removeClass('modal-xl').removeClass('modal-lg').removeClass('modal-sm').removeClass('modal-fullscreen');
        $modal_dialog.addClass($(target).find('.modal-dialog').attr('class'));
        $modal_content.html(default_template);
      },
      success: function (html) {
        $modal_content.html(html);
      }
    })

  });

$(document).on('click', '.btn-modal-back', function (e) {
    e.preventDefault();
    const $this = $(this);
    const redirect = $this.attr('data-redirect');
    const options = JSON.parse($this.attr('data-options'));

    if (options && typeof options.grid && options.grid === true) {
        let modal_dialog_size = null;
        if (['modal-lg', 'modal-sm', 'modal-fullscreen', 'modal-xl'].includes(options.class)) {
            modal_dialog_size = options.class;
        } else {
            modal_dialog_size = 'modal-lg';
        }

        const $parent = $this.closest('.modal');
        const $modal_content = $parent.find('.modal-content');
        const $modal_dialog = $parent.find('.modal-dialog');

        $modal_dialog.removeClass('modal-xl').removeClass('modal-lg').removeClass('modal-sm').removeClass('modal-fullscreen');
        $modal_dialog.addClass(modal_dialog_size);
        $modal_content.html(default_template);
        $modal_content.load(redirect, function (html) {
            //$modal_content.html(html);
        });
    }
});


$(document).on('click', '.link-view-child', function (e) {
    e.preventDefault();
    $('.label-infos').find('.view-libelle').slideUp(100);
    const $this = $(this);
    const $icon = $this.find('.link-icon');
    const $container = $this.next('.view-libelle');
    const $label = $this.prev('.label-parent');
    const parent = $this.data('parent');
    let plan_facturations = [];
    $('#info_facture_forfaits').find('input:checkbox').each(function () {
        const $this = $(this);
        if ($this.is(':checked')) {
            plan_facturations.push($this.val().split('_')[1]);
        }
    });


    $('#info_facture_planFacturation').find('input:radio').each(function () {
        const $this = $(this);
        if ($this.is(':checked')) {
            plan_facturations.push($this.val());
        }
    });

    if ($this.hasClass('active-link')) {
        $container.html('').slideUp(100);
        $this.removeClass('active-link');
    } else {
        $this.addClass('active-link');
        $.ajax({
            url: $this.attr('href'),
            data: {plan_facturation: plan_facturations},
            beforeSend: function () {

            },
            success: function (html) {
                $container.slideDown(100).html(html);
                let storage_libelles = JSON.parse(localStorage.getItem('fac_libelles')) || {};
                let values = storage_libelles[parent] || [];
                
                for (let v of values) {
                    let $input = $('[data-value="' + v +'"]');
                    
                    $input.prop('checked', true);
                }
            },
            error: function () {
                $container.html('Une erreur est survenue lors du chargements des infos');
                $this.removeClass('active-link');
            }
        })
    }
});



function load_tab_content(url, hash, hash_key, url_key) {
    if (hash_key && url_key) {
        localStorage.setItem(hash_key, hash);
        localStorage.setItem(url_key, url);
    }
    $.ajax({
        url: url,
        cache: false,
        beforeSend: function() {
            //console.log(hash);
            $(`#${hash}`).html('<p class="text-center">Chargement des données</p>');
        },
        success: function(content) {
            $(`#${hash}`).empty().html(content);
        },
        error: function(jqXhr, textStatus, errorThrown) {
            let html = '';
            if (jqXhr.status != 404) {
                html = '<p class="text-center">Erreur interne du serveur</p>';
            } else {
                html = '<p class="text-center">URL introuvable</p>';
            }
            $(`#${hash}`).empty().html(html);
        }
    });
}


function reload_data_table($grid, url) {
    const id = $grid.find('table').attr('id');
    $.ajax({
        url: url,
        method: 'POST',
        beforeSend: function () {
            //$('#page-loader').removeClass('display-none');
            $grid.find(`#${id}_processing`).show();
        
        },
        success: function(json) {
            
            var table = $grid.find(`#${id}`).DataTable();
            table.ajax.reload( null, false );
        },
        error: function (jqXHR, exception) {
            
        },
        complete: function () {
        // $('#page-loader').addClass('display-none');
            $grid.find(`#${id}_processing`).hide();
        }
    });
}

function reload_page(url, index = 0, persist_flash = false, data = null, is_set = false, ajax_container = {}, grid_wrapper = null) {
    if (data) {
        const $grid = $(grid_wrapper ? grid_wrapper: '.grid-dt-wrapper');
        let id;
        if ($grid.find('.dataTables_scrollBody').length) {
            id = $grid.find('.dataTables_scrollBody').find('table').attr('id');
        } else {
            id = $grid.find('table').attr('id');
        }
       

        if ($('.nav-content-tabs').length) {
            const id = $('.nav-content-tabs').attr('id');
            //alert($('.nav-content-tabs').attr('id'))
            const storage_key = `${id}_current_index`.replace('-', '_');
            //alert(storage_key)
            const current_index = is_set ? index : (localStorage.getItem(storage_key) || index);
            //alert(current_index)
            load_tab(id);
        } else {
            
            
            $.ajax({
                url: url,
                method: 'POST',
                beforeSend: function () {
                    //$('#page-loader').removeClass('display-none');
                    $grid.find(`#${id}_processing`).show();
                
                },
                success: function(json) {
                    var table = $grid.find(`#${id}`).DataTable();
                    table.ajax.reload( function () {
                        if ($('.active-li').find('.badge')) {
                        
                            const $badge = $('.active-li').find('.badge');
                            let old_value = +$badge.text();
                            let current_value = table.rows().count()
                            $badge.text(current_value);
                            if (current_value == 0) {
                                $badge.removeClass('badge-success').addClass('badge-dark');
                            }
                        }
                    }, false );

                   
                },
                error: function (jqXHR, exception) {
                    
                },
                complete: function () {
                // $('#page-loader').addClass('display-none');
                    $grid.find(`#${id}_processing`).hide();
                    $grid.addClass('reload-footer');
                }
            });
        }
    } else {
        $('#page-loader').removeClass('display-none');
        let inner_container, wrapper_container;
        if (ajax_container.inner && ajax_container.wrapper) {
            inner_container = ajax_container.inner;
            wrapper_container = ajax_container.wrapper;
        } else {
            inner_container = '.page-content-inner';
            wrapper_container = '#page-content-wrapper';
        }
        console.log(inner_container, wrapper_container);
        $(`${inner_container}`).load(`${url} ${wrapper_container}`, () => {
            $('#page-loader').addClass('display-none');


            init_select2();
            init_date_picker();
            
            //history.pushState({}, '', url);
         
            //$('.alert-flash').addClass('d-none');
            //alert($('.nav-content-tabs').length)
            if ($('.nav-content-tabs').length) {
                const id = $('.nav-content-tabs').attr('id');
                //alert($('.nav-content-tabs').attr('id'))
                const storage_key = `${id}_current_index`.replace('-', '_');
                //alert(storage_key)
                const current_index = localStorage.getItem(storage_key) || index;

                const $link = $(`#${id} li:eq(${current_index}) a`);
                //alert(current_index)
                load_tab(id);

                //console.log($('.nav-content-tabs'));
            }
            console.log($('#sw_card_sticky'));

            KTLayoutStickyCard.init('sw_card_sticky');
            $('.alert-flash').each(function() {
                const $this = $(this);
                if (!$this.hasClass('alert-success')) {
                    $this.hide();
                } else {
                    if (persist_flash) {
                        $this.removeClass('d-none');
                    } else {
                        $this.slideUp(5000);
                    }
                }
            });
            if (localStorage.getItem('reopen_on_page_load')) {
                const [elt, index] = localStorage.getItem('reopen_on_page_load').split('|');
                //alert('elt')
                if ($(elt).length) {
                    const $target = $(elt + ' li:eq(' + index + ') a').tab('show');
                    const [, hash] = $target.get(0).href.split('#');
                    load_tab_content($target.data('href'), hash);
                }
            }
            //$('body').scrollTop($('.alert-flash').position().top);
        });
    }

}

$(function() {

    let modals = new Set();

    $('body').on('hidden.bs.modal', '.modal', function() {
       
        const $this = $(this);
        $this.removeData('bs.modal');
        const old_class = $this.find('.modal-dialog').attr('class');
     
        $this.find('.modal-dialog').removeClass('modal-fullscreen modal-sm modal-md modal-lg modal-xl').addClass($this.attr('data-class') || old_class);
    });


    /*$('body').on('kpl:reload-modal', function(e, $modal) {
       console.log(e, $modal);
        const $this = $(this);
        $this.removeData('bs.modal');
        const old_class = $this.find('.modal-dialog').attr('class');
     
        $this.find('.modal-dialog').removeClass('modal-fullscreen modal-sm modal-md modal-lg modal-xl').addClass($this.attr('data-class') || old_class);
    });*/


    $(document).on('click', '.btn-ajax-etat', function (e) {
        e.preventDefault();
        const $this = $(this);
        const $btn = $this;
        const $form = $this.closest('form');
        const data = {};
        if ($this.attr('name')) {
            data[$this.attr('name')] = 1;
        }

        /*let iframe_url = $form.find('#form_iframeUrl').val();
        let grid_url = $form.find('#form_gridUrl').val();*/
        const $zone_data = $('#sw__print__zone_data');
        const $print_btn = $('.sw__print__zone_btn');
        const $excel_btn = $('.sw__export__zone_btn');
        const $pdf_zone = $('#sw__print__pdf_zone');
        const $grid_zone = $('#sw__print__grid_zone');
        const formData = $form.serializeArray();
        const $no_data_zone = $zone_data.find('#no-data-zone');
        let params = {};

        for (let item of formData) {
            let matches = item['name'].match(/\[([a-z]+)\]/i);
            if (matches !== null) {
                if (matches[1] != '_token') {
                    params[matches[1]] = item['value'];
                }
               
            }
        }

        let query_string = new URLSearchParams(params);

       


        $form.ajaxSubmit({
            cache: false,
            data: data,
            beforeSend: () => {
                $this.addClass('spinner spinner-white spinner-right');
                $pdf_zone.addClass('d-none');
                $print_btn.addClass('d-none').removeClass('iframe-view');
                $excel_btn.addClass('d-none');
                $grid_zone.addClass('d-none');
                $no_data_zone
                .removeClass('d-none')
                    .html(`<div class="d-flex flex-row justify-content-center tab-loader-text">
                    <div class="p-2">
                        <div class="spinner spinner-primary  spinner-track spinner-lg"></div> 
                    </div>
                    <div class="p-2">Chargement des données</div>
                </div>`);
            },
            beforeSubmit: function(arr, $form) { 
                $this.prop('disabled', true);
                if (localStorage.getItem('prevent_submit') == $form.attr('name')) {
                    $this.prop('disabled', false);
                    $this.removeAttr('disabled');
                    return false;
                }             
            },
            complete: () => {
                $this.removeClass('spinner spinner-white spinner-right');
                $this.prop('disabled', false);
            },
            success: (data, status, $xhr, $form) => {
                console.log(data);
                $no_data_zone.addClass('d-none');
                $excel_btn.addClass('d-none');
                if (data.statut == 1) {
                    let grid_url = data.gridUrl;
                    let iframe_url = data.iframeUrl;
                    let excel_url = data.excelUrl;
                    
                    if (iframe_url.indexOf('?') == -1) {
                        iframe_url += '?' + query_string;
                    }
                  
                   
                    if (grid_url) {
                        grid_url += '?' + query_string;
                        if (iframe_url) {
                            $print_btn.removeClass('d-none');
                            
                            $grid_zone.removeClass('d-none').load(grid_url);
                            $.ajaxSetup ({
                                // Disable caching of AJAX responses
                                cache: false
                            });
                            $pdf_zone.find('iframe').attr('src', iframe_url);
                        }
                    } else if (iframe_url) {
                        $pdf_zone.removeClass('d-none').find('iframe').attr('src', iframe_url);
                        $excel_btn.removeClass('d-none').attr('href', excel_url);
                    }
                } else {
                    $pdf_zone.find('iframe').removeAttr('src');
                    $no_data_zone.removeClass('d-none');
                    $excel_btn.addClass('d-none');
                }
                
            },
            error: ($xhr) => {
                let tpl = '';
                let showAlert = false;
                $excel_btn.addClass('d-none');
               
                if ($xhr.responseJSON) {
                    let data = $xhr.responseJSON;
                    let message = data.message;
                    showAlert = data.showAlert;
                   
                    if (Array.isArray(message)) {
                        for (let _message of message) {
                            tpl += `<p class="mb-0">${_message}</p>`;
                        }
                    } else {
                        tpl = `<p class="mb-0">${message}</p>`;
                    }
                } else {
                    tpl = 'Erreur interne du serveur';
                }

                if (showAlert) {
                    $alert = Swal.fire({
                        html: tpl ? tpl : 'Erreur interne du serveur', 
                        icon: 'error'
                    });
                } else {
                   
                    $no_data_zone.removeClass('d-none').html(tpl ? tpl : 'Erreur interne du serveur');
                }

               
            }

        });
    });


    $(document).on('click', '.btn-scan', function (e) {
        e.preventDefault();
        const $this = $(this);
        const $btn = $this;
        const $form = $this.closest('form');
        const data = {};
        if ($this.attr('name')) {
            data[$this.attr('name')] = 1;
        }

        data['site'] = g_current_site;


        $.ajax({
            url: scan_url,
            cache: false,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: () => {
                $this.find('.spinner-ajax').removeClass('d-none');
                $this.prop('disabled', true);
            },
           
            complete: () => {
               
                //$loader.addClass('d-none');
                $this.find('.spinner-ajax').addClass('d-none');
                $this.prop('disabled', false);
            },
            success: (data) => {
                if (data.statut == 1) {
                    const message = data.message;
                    const file_id = message.id;
                    $form.find('.file-scan').val(file_id);
                    $form.find('.pdf-preview').attr('src', message.url);
                }
            },
            error: ($xhr) => {
                let tpl = '';
                let showAlert = false;
                if ($xhr.responseJSON) {
                    let data = $xhr.responseJSON;
                    let message = data.message;
                    showAlert = data.showAlert;
                   
                    if (Array.isArray(message)) {
                        for (let _message of message) {
                            tpl += `<p class="mb-0">${_message}</p>`;
                        }
                    } else {
                        tpl = `<p class="mb-0">${message}</p>`;
                    }
                } else {
                    tpl = 'Erreur interne du serveur';
                }

                if (showAlert) {
                    $alert = Swal.fire({
                        html: tpl ? tpl : 'Erreur interne du serveur', 
                        icon: 'error'
                    });
                } else {
                    console.log(tpl ? tpl : 'Erreur interne du serveur');
                }

               
            }

        });
    });


    // Traitement au moment de la validation, au cliqque du bouton valider
    $(document).on('click', '.btn-ajax, .btn-inner-ajax', function(e) {
        //Formaulaires AJAX
        e.preventDefault();
        e.stopImmediatePropagation();

      
        const $this = $(this);
        const $btn = $this;
        const $form = $this.closest('form');
       
        const form_id = $form.attr('id');
        //const $loader = $form.find('.loader');
        const $modal = $this.closest('.modal');
        const $nav = $this.data('nav');
        let block_id;

        if ($this.hasClass('wrapper-has-blocker')) {
            block_id = $('.has-block-ui').attr('id');
        }
        const data = {};
        if ($this.attr('name')) {
            data[$this.attr('name')] = 1;
        }

        $form.ajaxSubmit({
            cache: false,
            data: data,
            global: false,
            beforeSend: () => {
                console.log('Before send....');
                if (!$this.hasClass('btn-inner-ajax')) {
                    $('.ajax-content').html('');
                }

                if (block_id) {
                    KTApp.block('#' + block_id, {
                        overlayColor: 'red',
                        opacity: 0.1,
                        message: $('#' + block_id).data('loadingtext'),
                        state: 'primary' // a bootstrap color
                    });
                }
               
                
               
                //$loader.removeClass('d-none');
                $this.find('.spinner-ajax').removeClass('d-none');
            },
            beforeSubmit: function(arr, $form) { 
                
                if (localStorage.getItem('prevent_submit') == $form.attr('name')) {
                    return false;
                }   
                $this.prop('disabled', true);

            },
            complete: () => {
                //$loader.addClass('d-none');
                $this.find('.spinner-ajax').addClass('d-none');
                $this.prop('disabled', false);
                if (block_id) {
                    KTApp.unblock('#' + block_id);
                }
               
            },
            success: (data, status, $xhr, $form) => {
                const keys = Object.keys(data);
                const close_html = '<a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>';
                let message    = data.message;
                const redirect   = data.redirect;
                const actions    = data.actions;
                const ajax_container = data.ajaxContainer;
                const gridWrapper = data.gridWrapper;
                const url = data.url;
                const reload__grid = data.data;
                const tabId = data.tabId;
                const close_btn = data.closeBtn;
                const push_dgr = data.pushDgr;
                const push_data = data.pushData;
                const vals = data.vals || {};
                let $alert;
                const _reload_page = keys.indexOf('reloadPage') >= 0 ? data.reloadPage: true;
                const showAlert = keys.indexOf('showAlert') >= 0 ? data.showAlert : false;
                const content = data.content;
                const resubmit = data.resubmit;
              
                if (data.statut) {
                    
                    const $alertFeedback = $('.ajax-success', $form);
                    $('.btn-select-all').find('.badge').text(0);
                    $('#checkbox-list').html('');
                    $('.select-line').each(function () {
                        $(this).prop('checked', false);
                    });

                    for (const _field of Object.keys(vals)) {
                        
                        let $item = $('.val-' + _field);
                        if ($item.is(':input')) {
                            $item.val(setValue(vals[_field]));
                        } else {
                            $item.text(vals[_field]);
                        }
                        
                    }

                    if (resubmit && $('.btn-resubmit').length) {
                        $('.btn-resubmit').trigger('click');
                    }


                    if (push_data) {
                        $.ajax({
                            url: Routing.generate('push_exploitation', {type: push_data.discr}, true),
                            type: 'POST',
                            data: push_data.data,
                            dataType: 'json'
                        });
                    }
                    
                    if (push_dgr) {
                        $.ajax({
                            url: Routing.generate('push_dossier_degroupage', {type: push_dgr.type}, true),
                            type: 'POST',
                            data: push_dgr.data,
                            dataType: 'json',
                            error: function () {
                                reload_dgr_supervision(push_dgr.data, false);
                            }
                        });
                    }

                    if (data.message && !showAlert) {
                        $alertFeedback.removeClass('d-none').find('.alert-text').html(data.message);
                    }
                    $('.ajax-error', $form).addClass('d-none').find('.alert-text').html('');

                    
                    if (data.data || !redirect || (redirect.indexOf('#modal') === -1)) {
                        
                        if (redirect && $modal.length && data.modal !== false) {
                            $modal.modal('hide');
                        }
                        $alert = new Promise((resolve, reject) => resolve());

                        if (showAlert) {
                            $alert = Swal.fire({
                                html: data.message, 
                                icon: 'success'
                            });
                        }

                        if (url && tabId) {
                            const hash = url.tab;
                            const currentTab = url.current_tab;
                            const $link = $('#' + tabId).find('[href="' + hash + '"]');
                            const $li = $link.parent('li');
                            $li.removeClass('d-none');
                            if (!$link.data('href')) {
                                $link.attr('data-href', url.url);
                            }
                            
                            load_tab(tabId, null, $li.index());

                            if (currentTab && currentTab.url && currentTab.tab) {
                               
                                const $oldLink = $('#' + tabId).find('[href="' + currentTab.tab + '"]');
                                $oldLink.removeData('href');
                                $oldLink.attr('data-href', currentTab.url);
                            }
                        } else {
                            //console.log( data );
                            if (data.fullRedirect) {
                                $alert.then(() => {
                                    document.location.href = redirect;
                                });
                            } else {
                               
                                if (redirect && !actions && _reload_page) {
                                    console.log('HERE');
                                    $alert.then(() => reload_page(redirect, 0 , data.persistFlash, data.data, false, ajax_container, gridWrapper));
                                }

                                if (data.message) {
                                    $(window).scrollTop($alertFeedback.position().top);
                                }
                                
                            }
                        }
                        
                        
                        
                        if (actions && typeof actions.action != 'defined') {
                            
                            switch (actions['action']) {
                                case 'switch_tab':
                                    load_tab(actions.target, {id: actions.id}, actions.index);
                                    break;
                                case 'reload_modal':
                                    $alert.then(() =>  _reload_modal( $(`${actions.target}`), actions.url || redirect));
                                    break;
                                case 'remove_assigned':
                                    const $parent = $(`${actions.target}`);
                                    const $link = $parent.find('[href="#'+actions.etat+'"]');
                                    if (actions.count == 0) {
                                        $link.find('.label-dot').remove();
                                    }
                                    reload_page(redirect, 0 , data.persistFlash, data.data, false, ajax_container, gridWrapper);
                                    break;
                                case 'reload_fragment':
                                    $(`${actions.fragment}`).html(`${actions.content}`);
                                    break;
                                case 'open_modal':
                                    const $selector = $(`${actions.target}`);
                                    //const modal = new bootstrap.Modal(document.getElementById(`${actions.target}`));
                                    //$selector.modal('toggle');
                                    if ($selector[0] instanceof jQuery) {
                                        $selector[0].toggle();
                                    } else {
                                        $selector.modal('toggle');
                                    }
                                    
                                    $selector.find('.modal-content').load(actions.url, () => {
                                        $('body').addClass('modal-open');
                                        reload_page(redirect, 0 , false, reload__grid, false, ajax_container, gridWrapper);
                                    });

                                   
                                   
                                    break;
                                case 'reload_grid':
                                    $alert.then( () => {
                                        $alertFeedback.addClass('d-none');
                                        const $grid_parent_selector = $(`${actions.target}`);
                                        const $grid_id = $(`${actions.grid_id}`);
                                        const $modal_body = $(`${actions.modal_body}`);
                                        
                                        $modal_body.closest('.modal-dialog').removeClass('modal-sm modal-fullscreen modal-lg modal-xl')
                                            .addClass(actions.modal_dialog || 'modal-lg');
                                        $modal_body.closest('.modal-content').html(default_template).load(redirect);
                                        ;
                                        
                                    });
                                   
                                    
                                   
                                    break;
                                case 'update_data':
                                    const data = actions.data;
                                    const field_id = actions.fieldId;
                                    for (const key of Object.keys(data)) {
                                        $(`.field-${field_id}_${key}`).html(`<b>${data[key]}</b>`).removeClass('d-none');
                                        $(`#${field_id}_${key}`).closest('.form-block').addClass('d-none');
                                    }
                                    $btn.addClass('d-none');
                                    $btn.siblings('.btn-edit-block').removeClass('d-none');
                                    $btn.siblings('.btn-close-block').addClass('d-none');
                                    setTimeout(function () {
                                        $('.ajax-success', $form).addClass('d-none').find('.alert-text').html('');
                                    }, 5000)
                                   
                                    break;
                                        
                                
                            }
                        }

                        if (close_btn) {
                            $('.btn-to-close').remove();
                        }
                    } else {
                        if (showAlert) {
                            $alert = Swal.fire("", data.message, "success");
                        }
                        
                      
                        let [url, modal_id] = redirect.split('#');
                        modal_id = modal_id.replace('modal', '');
                        let opened_modals = [];
                        modals_array = Array.from(modals);
                        let prev_index = 0;
                        modals_array.forEach((val, index) => {
                            if (val.id == modal_id) {
                                prev_index -= 1;
                            }
                        });
                        $('#' + modal_id).modal('hide');
                        $('#' + modals_array[0]).addClass('reload-page');
                        const $current_modal = $('#' + modals_array[prev_index >= 0 ? prev_index : 0]);
                        /*const grid_hash = localStorage.getItem('__grid_hash');
                        const $grid_wrapper = $('#grid-wrapper-' + grid_hash, $current_modal);
                        if ($grid_wrapper.length) {
                            const $grid_loader = $grid_wrapper.find('.grid-overlay');
                            $grid_loader.removeClass('display-none');
                            $grid_wrapper.load($('#grid_' + grid_hash).attr('action') + ' #grid-table-' + grid_hash, function() {
                                $grid_loader.addClass('display-none');
                            });
                        } else {
                            
                        }*/

                        
                    }
                    $modal.scrollTop($('.ajax-success', $form).position().top);
                    
                } else {
                    let tpl = '';
                    if (Array.isArray(message)) {
                        message = [...new Set(message)];
                        for (let _message of message) {
                            tpl += `<p class="mb-0">${_message}</p>`;
                        }
                    } else {
                        tpl = `<p class="mb-0">${message}</p>`;
                    }
                   
                    $('.ajax-error', $form).removeClass('d-none').find('.alert-text').html(tpl);
                    $('.ajax-success', $form).addClass('d-none').find('.alert-text').html('');
                    $modal.scrollTop($('.ajax-error', $form).position().top);
                    $(window).scrollTop($('.ajax-error', $form).position().top);
                }
            },
            error: ($xhr) => {
                let tpl = '';
                let showAlert = false;
                if ($xhr.responseJSON) {
                    let data = $xhr.responseJSON;
                    let message = data.message;
                    showAlert = data.showAlert;
                   
                    if (Array.isArray(message)) {
                        for (let _message of message) {
                            tpl += `<p class="mb-0">${_message}</p>`;
                        }
                    } else {
                        tpl = `<p class="mb-0">${message}</p>`;
                    }
                } else {
                    tpl = 'Erreur interne du serveur';
                }

                if (showAlert) {
                    $alert = Swal.fire({
                        html: tpl ? tpl : 'Erreur interne du serveur', 
                        icon: 'error'
                    });
                } else {
                    $('.ajax-error', $form).removeClass('d-none').find('.alert-text').html(tpl ? tpl : 'Erreur interne du serveur');
                    $('.ajax-success', $form).addClass('d-none').find('.alert-text').html('');
                }

                if ($('.ajax-error', $form).length) {
                    $modal.scrollTop($('.ajax-error', $form).position().top);
                    $(window).scrollTop($('.ajax-error', $form).position().top);
                }

                
            }
        });
    }).on('click', '.button-ajax', function(e){
        e.preventDefault();
        const $this = $(this);
        const $form = $this.closest('form');
        const form_id = $form.attr('id');
        //alert('button-ajax')
        $form.ajaxSubmit({
            cache: false,
            beforeSend: () => {
                $this.addClass('spinner spinner-white spinner-right')
            },
            complete: () => {
                $this.removeClass('spinner spinner-white spinner-right')
            },
            success: (data, status, $xhr, $form) => {
                const close_html = '<a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>';
                const message    = data.message;
                const redirect   = data.redirect;
                const actions    = data.actions;
                const entity     = data.entity;
                if (data.statut) {
                    console.log(data.entity)
                    table.row.add( [
                        data.entity['code'],
                        data.entity['libelle']
                    ] ).draw( false );
                    /*$('body').notify({
                        message: message,
                        type: 'success'
                    });*/
                    //$('body').toast(data.message)
                    $form[0].reset()                
                }
            },
            error: (data) => {
                /*$('body').notify({
                    message: data.message,
                    type: 'danger'
                }); */
                //$modal.scrollTop($('.ajax-error').position().top);
            }
        });
    }).on('click', '.prevent-default', function(e) {
        e.preventDefault();
    }).on('click', '.link-param', function(e) {

    })/*.on('click', '#sticky-submit', function(e){
        console.log('sticky')
        parent  = $(this).closest('div.card')
        form    = parent.find('form:eq(0)')
        form.submit()
    })*/;

/*************************************************MODALES*******************************************************/
    // vider le contenu de la modale à sa fermeture
    $('.modal').on('hide.bs.modal', function(e) {
        const $this = $(this);

        const $target = $(e.target);
        if ($target.hasClass('no-ajax')) {
            return;
        }
        if (!$this.closest('.note-editor').length) {
            modals.delete($this.attr('id'));

            const default_template = `
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"</h5>
                        <div class="btn btn-icon btn-sm ms-2" data-bs-dismiss="modal" aria-label="Close">
                            <span class="svg-icon svg-icon-2x text-white">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"></rect>
                                    <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"></rect>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div class="modal-body text-center">
                        <div class="spinner spinner-track spinner-primary mr-15">&nbsp;&nbsp;Chargement des données...</div>
                        <span></span>
                    </div>
                `;

            $this.find('.modal-content').html('').append(default_template);
            if ($this.hasClass('reload-page')) {
                $this.removeClass('reload-page');
                reload_page(current_url);
                $('.alert-flash').remove();
            }
        }
        const current_hash = document.location.hash;
        if (current_hash.indexOf('#modal-ref') === 0) {
            document.location.hash = '';
        }

        if (current_hash.indexOf('#sendMail') === 0) {
            document.location.hash = '';
        }
    });


    // Appel chargement du contenu pendant l'ouverture du modal
    $('.modal').on('show.bs.modal', function (e)
    {
        const $target = $(e.relatedTarget);
        
        const $this = $(this);
        const options = $this.data('options');

        

        if ($target.attr('href') && $target.attr('href')[0] != '#') {
            
            const $modal = $this.find('.modal-content');
            $modal.load($target.attr('href'), function () {
                const $tr = $('.tr-dossier');
                const href = $this.attr('href');
                let dossiers = [];
                let redressements = [];
                $tr.each(function () {
                    let $input = $(this).find('.input-dossier');
                    if ($input.hasClass('input-decl')) {
                        dossiers.push($input.val());
                    }

                    if ($input.hasClass('input-redressement')) {
                        redressements.push($input.val());
                    }
                    
                });
                



                $modal.find('#bordereau_douane_dossier').val(dossiers.join(','));
                $modal.find('#bordereau_douane_redressement').val(redressements.join(','));
            });


            
        }

        if ($target.attr('data-href')) {
            $this.find('.modal-content').load($target.attr('data-href'));
        }

        
    });

    function _reload_modal($target, url, className = '') {
        const $content =  $target.find('.modal-content');
        

        $.ajax({
            url: url,
            beforeSend: function () {
                $content.html(default_template);
            },
            
            success: function (content) {
                $content.html(content);
            },
            error: function () {
                $content.empty().html(`
                    <div class="alert alert-custom alert-notice alert-light-danger fade show" role="alert">
                    <div class="alert-icon"><i class="flaticon-warning"></i></div>
                    <div class="alert-text">Erreur interne du serveur</div>
                
                    </div>
                `)
            }
        });

    }


    $('.menu-item-active').closest('.current_ancestor').addClass('menu-item-open');

    $('body').each(function () {
        const $this = $(this);
        const current_menu = $this.attr('data-current-menu');
    
        if (current_menu) {
            const $parent_li = $('.' + current_menu).closest('li');
            $parent_li.addClass('menu-item-active');
            $parent_li.closest('.menu-submenu').closest('li').addClass('menu-item-open menu-item-here');
        }
    });


    $(document).ajaxError(function (jqEvent, jqXhr, options) {
        const url = options.url;
        const method = options.type;
        const dataType = options.dataType;
        const $target = $(jqEvent.currentTarget.activeElement);
        console.log(options);
        if ($target.find('.modal-body') && method.toLowerCase() == 'get' && dataType == 'html') {
            const modal_content = ` 
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Erreur</h5>
            <div class="btn btn-icon btn-sm  ms-2" data-bs-dismiss="modal" aria-label="Close">
                <span class="svg-icon svg-icon-2x text-white">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"></rect>
                        <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"></rect>
                    </svg>
                </span>
            </div>
        </div>
        <div class="modal-body">
        <div class="alert alert-danger d-flex align-items-center p-5 mb-10 ajax-error d-none">
                <!--begin::Svg Icon | path: icons/duotune/general/gen048.svg-->
                <span class="svg-icon svg-icon-2hx svg-icon-danger me-4">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.3" d="M12 10.6L14.8 7.8C15.2 7.4 15.8 7.4 16.2 7.8C16.6 8.2 16.6 8.80002 16.2 9.20002L13.4 12L12 10.6ZM10.6 12L7.8 14.8C7.4 15.2 7.4 15.8 7.8 16.2C8 16.4 8.3 16.5 8.5 16.5C8.7 16.5 8.99999 16.4 9.19999 16.2L12 13.4L10.6 12Z" fill="currentColor"/>
                        <path d="M22 12C22 17.5 17.5 22 12 22C6.5 22 2 17.5 2 12C2 6.5 6.5 2 12 2C17.5 2 22 6.5 22 12ZM13.4 12L16.2 9.20001C16.6 8.80001 16.6 8.19999 16.2 7.79999C15.8 7.39999 15.2 7.39999 14.8 7.79999L12 10.6L9.2 7.79999C8.8 7.39999 8.2 7.39999 7.8 7.79999C7.4 8.19999 7.4 8.80001 7.8 9.20001L10.6 12L7.8 14.8C7.4 15.2 7.4 15.8 7.8 16.2C8 16.4 8.3 16.5 8.5 16.5C8.7 16.5 9 16.4 9.2 16.2L12 13.4L14.8 16.2C15 16.4 15.3 16.5 15.5 16.5C15.7 16.5 16 16.4 16.2 16.2C16.6 15.8 16.6 15.2 16.2 14.8L13.4 12Z" fill="currentColor"/>
                    </svg>
                </span>
                <!--end::Svg Icon-->
                <div class="d-flex flex-column alert-text">
                    Une erreur est survenue                                        
                </div>
                <!--begin::Close-->
                <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                    <span class="svg-icon svg-icon-2x svg-icon-light">...</span>
                </button>
                <!--end::Close-->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal">Annuler</button>
        </div>`;
                $target.find('.modal-title').text('Erreur')
                $target.find('.modal-content').html(modal_content);
        }
    });


    function reload_dgr_supervision(data, dispatch = true) {
        const current = data.current; 
        const current_etat = current.etat; 
        const etat = current_etat;
        const groupe = data.groupe;
        const $current_link = $(`[href="#${current_etat}"]`); 
        const updates = data.updates; 
        const id = `subParamLinksTabDegroupage-${groupe}-${etat}`; 
        const active_etat = $('#' + id).find('.active').attr('data-etat');

        if ($current_link.length) { 
            $current_link.find('.counter-number').text(current.stats.count); 
        } 
                
        for (const _etat of Object.keys(updates)) { 
            let $link = $(`[href="#${_etat}"]`); 
            $link.find('.counter-number').text(updates[_etat]['count']); 
            const id = 'subParamLinksTabDegroupage-' + updates[_etat]['groupe'] + '-' + _etat;
            if ($('#' + id).length) {
                load_tab(id);
            }
                   
        }

        if (current_etat == active_etat) {
            load_tab(id);
        }

      
    }


    $('body').on("click", ".dismiss-alert", function (e) {
       $(this).closest('.alert').addClass('d-none');
    });

   


    $('body').on('click', '.has-alert', function (e) {
        e.preventDefault();
        const $this = $(this);
        const message = $this.attr('data-message');
        if (message) {
            const options = JSON.parse(message);
            if (options.total > 0) {
                Swal.fire({
                    title: 'Attention',
                    text: options.message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Facturer le dossier',
                    cancelButtonText: 'Annuler',
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.location.href = $this.attr('href');
                    }
                    
                })
            } else {
                document.location.href = $this.attr('href');
            }
        }
    });


    var button = document.querySelector(".btn-has-loader");

    if (button) {
          // Handle button click event
        button.addEventListener("click", function() {
            // Activate indicator
            button.setAttribute("data-kt-indicator", "on");

            // Disable indicator after 3 seconds
            setTimeout(function() {
                button.removeAttribute("data-kt-indicator");
            }, 3000);
        });
    }

    if (navigator.userAgent.toLowerCase().indexOf('firefox') !== -1)
    {
        const allModals = document.querySelectorAll('.modal:not(.initialized)');

        allModals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', function () {
                bootstrap.Modal.getInstance(this).handleUpdate();
                this.classList.add('initialized');
                //alert(2);
            });
        });
    }

});


$(function () {
    
});