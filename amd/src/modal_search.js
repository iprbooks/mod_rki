define(["exports", "jquery", "core/ajax", "core/modal_factory", "core/modal_events", "core/notification", "core/modal",
        "core/custom_interaction_events", "core/modal_registry", "mod_rki/modal_book", "mod_rki/modal_book_handle"],
    function (exports, $, ajax, ModalFactory, ModalEvents, Notification, Modal,
              CustomEvents, ModalRegistry) {

        $.fn.serializeAssoc = function () {
            var data = {};
            $.each(this.serializeArray(), function (key, obj) {
                var a = obj.name.match(/(.*?)\[(.*?)\]/);
                if (a !== null) {
                    var subName = a[1];
                    var subKey = a[2];

                    if (!data[subName]) {
                        data[subName] = [];
                    }

                    if (!subKey.length) {
                        subKey = data[subName].length;
                    }

                    if (data[subName][subKey]) {
                        if ($.isArray(data[subName][subKey])) {
                            data[subName][subKey].push(obj.value);
                        } else {
                            data[subName][subKey] = [];
                            data[subName][subKey].push(obj.value);
                        }
                    } else {
                        data[subName][subKey] = obj.value;
                    }
                } else {
                    if (data[obj.name]) {
                        if ($.isArray(data[obj.name])) {
                            data[obj.name].push(obj.value);
                        } else {
                            data[obj.name] = [];
                            data[obj.name].push(obj.value);
                        }
                    } else {
                        data[obj.name] = obj.value;
                    }
                }
            });
            return data;
        };

        let SELECTORS = {
            SEARCH_TEXTBOX: "[data-action='search_text']",
            START_SEARCH: "[data-action='search_button']",
            START_SEARCH_CLEAR: "[name='clear_search']",
            CANCEL_BUTTON: "[data-action='cancel_button']",
            CONTENT_BLOCK: "[data-action='content_block']",
            CONTENT_NAME: "[name='content_name']",
            TRIGGER_BOOK: ".trigger_book",
            ADD_BOOK: "[name='add_book']",
            CATEGORY_BUTTON: "[data-action='category_tree']",
            CATEGORY_BLOCK: "[data-action='categories']",
            LEVEL_BLOCK: "[data-action='levels']",
            BOOK_PAGINATION: "#books_pagination li.page-item",
            START_PAGE_CLASS: "page-start",
            END_PAGE_CLASS: "page-end",
            PROGRESS: ".loader",
        };
        let LIMIT_ON_PAGE = 10;

        /**
         * Constructor for the Modal
         *
         */
        let ModalSearch = function (root) {
            Modal.call(this, root);

            if (!this.getBody().find(SELECTORS.SEARCH_TEXTBOX).length) {
                Notification.exception({message: 'text box not found'});
            }
            if (!this.getBody().find(SELECTORS.START_SEARCH).length) {
                Notification.exception({message: 'search button not found'});
            }
        };

        ModalSearch.TYPE = 'mod_rki-search';
        ModalSearch.prototype = Object.create(Modal.prototype);
        ModalSearch.prototype.constructor = ModalSearch;

        ModalSearch.prototype.registerEventListeners = function () {
            Modal.prototype.registerEventListeners.call(this);
            let disabledEnterSubmit = function (e) {
                if (e.keyCode === 13) {
                    submitFunction(e);
                    return false;
                }
            };
            let disabledKeyUp = function (e) {
                if (e.keyCode !== false) {
                    e.preventDefault();
                    return false;
                }
            };
            let submitFunction = function (e) {
                e.preventDefault();
                e.stopPropagation();
                let id = $(SELECTORS.CATEGORY_BLOCK).attr('data-id');
                let levelId = $(SELECTORS.LEVEL_BLOCK).attr('data-id');
                let args = {
                    searchParam: $(e.target.form).serializeAssoc(),
                    page: 1,
                    limit: LIMIT_ON_PAGE,
                    catId: id,
                    levelId: levelId
                };
                $(SELECTORS.PROGRESS).toggleClass('hide');
                ModalSearch.prototype.getAjaxCall('mod_rki_search_books', args, ModalSearch.prototype.getSearchResult)
                    .then(function () {
                        ModalSearch.prototype.resetPagination();
                        $(SELECTORS.PROGRESS).toggleClass('hide');
                    });
            };

            let getCategoryTree = function (e) {
                e.preventDefault();
                e.stopPropagation();
                let categoryId = [null];
                let args = {
                    categoryId: categoryId
                };
                ModalSearch.prototype.getAjaxCall('mod_rki_category_tree', args, ModalSearch.prototype.printCategories);
            };

            let setPagination = function (e) {
                if ($(e.currentTarget).hasClass('disabled')) {
                    return true;
                }
                let page, maxPage;
                if ($(SELECTORS.CONTENT_BLOCK).attr('data-page') === undefined) {
                    maxPage = 0;
                } else {
                    maxPage = parseInt($(SELECTORS.CONTENT_BLOCK).attr('data-page'));
                }
                if ($(e.currentTarget).hasClass(SELECTORS.START_PAGE_CLASS)) {
                    if (maxPage > 0) {
                        page = 1;
                    } else {
                        page = 0;
                    }
                } else if ($(e.currentTarget).hasClass(SELECTORS.END_PAGE_CLASS)) {
                    page = maxPage;
                } else {
                    page = parseInt($(e.currentTarget).attr('data-page'));
                }
                let id = $(SELECTORS.CATEGORY_BLOCK).attr('data-id');
                let levelId = $(SELECTORS.LEVEL_BLOCK).attr('data-id');
                let args = {
                    searchParam: {'searchString': $(SELECTORS.SEARCH_TEXTBOX).val()},
                    page: page,
                    limit: LIMIT_ON_PAGE,
                    catId: id,
                    levelId: levelId
                };
                let prevPage = $(SELECTORS.BOOK_PAGINATION).find('a.prev').closest('li.page-item');
                let nextPage = $(SELECTORS.BOOK_PAGINATION).find('a.next').closest('li.page-item');
                let currentPage = $(SELECTORS.BOOK_PAGINATION).find('a.active').closest('li.page-item');
                if (page >= 2) {
                    $(prevPage).attr('data-page', page - 1);
                    $(prevPage).removeClass('disabled');
                } else if (page === 1) {
                    $(prevPage).attr('data-page', page - 1);
                    $(prevPage).addClass('disabled');
                }
                if (maxPage === page) {
                    $(nextPage).addClass('disabled');
                } else {
                    $(nextPage).removeClass('disabled');
                }
                $(nextPage).attr('data-page', page + 1);
                $(currentPage).attr('data-page', page);
                $(currentPage).find('a.active').text(page + ' из ' + maxPage);
                if (page !== 0 && maxPage !== 0) {
                    ModalSearch.prototype.getAjaxCall('mod_rki_search_books', args, ModalSearch.prototype.getSearchResult);
                }
            };

            this.getModal().on(CustomEvents.events.activate, SELECTORS.START_SEARCH, submitFunction.bind(this));
            this.getModal().on('click', SELECTORS.START_SEARCH_CLEAR, function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(SELECTORS.SEARCH_TEXTBOX).val('');
                $(SELECTORS.START_SEARCH).trigger('click');
                return false;
            });
            this.getModal().on('keypress', SELECTORS.SEARCH_TEXTBOX, disabledEnterSubmit.bind(this));
            this.getModal().on(CustomEvents.events.activate, SELECTORS.BOOK_PAGINATION, setPagination.bind(this));
            this.getModal().on('keydown', SELECTORS.CONTENT_NAME, disabledKeyUp.bind(this));

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function () {
                $(this).trigger('hide');
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CATEGORY_BUTTON, getCategoryTree.bind(this));
        };

        ModalSearch.prototype.getSearchResult = function (response) {
            $(SELECTORS.CONTENT_BLOCK).empty();
            let maxPage = Math.ceil(response.meta.total / LIMIT_ON_PAGE);

            if (response.meta.total > 0) {
                $.each(response.data, function (number, item) {
                    let descriptionBlock =
                        '<a class="" data-toggle="collapse" href="#collapseDescription' + item.id + '" role="button">' +
                        'Подробнее об издании' +
                        '</a><br>' +
                        '<div class="collapse" id="collapseDescription' + item.id + '">' +
                        '<span><strong>Описание: </strong>' + item.description + '</span><br><br>' +
                        '<span><strong>ISBN: </strong>' + item.isbn + '</span><br><br>' +
                        '</div>';

                    if (item.description === null || item.description === '') {
                        item.description = '';
                        descriptionBlock = '';
                    }
                    if (item.authors === null || item.authors === '') {
                        item.authors = '';
                    }
                    $(SELECTORS.CONTENT_BLOCK).append(
                        '<div class="row" data-id="' + item.book_id + '">' +
                        '<div class="col-sm-2" >' +
                        '<img src="https://www.ros-edu.ru/' + item.image + '" class="img-responsive thumbnail" alt="">' +
                        '</div>' +
                        '<div class="col-sm-8">' +
                        '<span class="book_title" data-val="' + item.title + '"><strong>Название: </strong>' +
                        item.title + '</span><br>' +
                        '<span class="book_authors" data-val="' + item.authors + '"><strong>Авторы: </strong>' +
                        item.authors + '</span><br>' +
                        '<span><strong>Издательство: </strong>' + item.pubhouses + '</span><br>' +
                        '<span><strong>Год издания: </strong>' + item.year + '</span><br>' +
                        '<span><strong>Тип издания: </strong>' + item.longtitle + '</span><br><br>' +
                        '<span class="book_title_additional" style="display: none" data-val="' + item.longtitle + '">' +
                        item.longtitle + '</span><br>' +
                        descriptionBlock +
                        '</div>' +
                        '<div class="col-sm-2">' +
                        '<button type="button" name="add_book" class="btn btn-sm" style="color: #a53436;' +
                        'background-color: white;border-color: #a53436;width: 140px;">Добавить</button>' +
                        '<button type="button" class="trigger_book btn btn-sm mt-2" style="color: #a53436;' +
                        'background-color: white;border-color: #a53436;width: 140px;">Предпросмотр</button>' +
                        '<br>' +
                        '</div>' +
                        '</div><hr>');
                });
            } else {
                $(SELECTORS.CONTENT_BLOCK).append('<div class="item h4">Книги не найдены</div>');
            }
            $(SELECTORS.CONTENT_BLOCK).attr('data-page', maxPage);
            $(SELECTORS.TRIGGER_BOOK).on('click', function (e) {
                let bookId = $(e.target).closest('.row').attr('data-id');
                let args = {
                    bookId: bookId,
                };

                ModalSearch.prototype.getAjaxCall('mod_rki_book_read_url', args, function (response) {
                    var win = window.open(response.data, '_blank');
                    win.focus();
                });
            });
            $(SELECTORS.ADD_BOOK).on('click', function (e) {
                let id = $(e.target).closest('.row').attr('data-id');
                let contentName = $('[name="content_name"]');
                contentName.val($(e.target).closest('.row').find('.book_title').attr('data-val'));
                contentName.removeClass('is-invalid');
                contentName.siblings('#id_error_content_name').text('');

                let resName = $('[name="name"]');
                let resAuthors = $(e.target).closest('.row').find('.book_authors').attr('data-val').split(",");
                let resTitle = $(e.target).closest('.row').find('.book_title').attr('data-val');
                let resType = $(e.target).closest('.row').find('.book_title_additional').attr('data-val');
                resName.val(resAuthors[0] + ', ' + resTitle + ": " + resType);
                resName.removeClass('is-invalid');
                resName.siblings('#id_error_content_name').text('');

                $('[name="content"]').val(id);
                $(SELECTORS.CANCEL_BUTTON).trigger('click');
            });
        };

        ModalSearch.prototype.printCategories = function (response) {
            $(SELECTORS.CATEGORY_BLOCK).empty();
            $.each(response.data, function (number, item) {
                $(SELECTORS.CATEGORY_BLOCK).append(
                    '<div style="cursor:pointer;color:#174c8d;background-color:white;" ' +
                    'class="item btn-sm' + (item.id === 0 ? ' bg-primary' : '') + '" data-id="' + item.id + '">' +
                    '<span>' + item.pagetitle +
                    '</span>' +
                    '</div>');

                $(SELECTORS.CATEGORY_BLOCK).find('[data-id="' + item.id + '"]').click({item: item}, function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    $(SELECTORS.CATEGORY_BLOCK).attr('data-id', $(e.currentTarget).attr('data-id'));

                    if (!$(this).hasClass('bg-primary')) {
                        $(this).addClass('bg-primary');
                        $(this).siblings().removeClass('bg-primary');
                    }

                    $(SELECTORS.START_SEARCH).trigger('click');
                });
            });

            $(SELECTORS.CATEGORY_BLOCK).prepend(
                '<div ' + 'class="btn-sm">' +
                '<h5>Категории</h5>' +
                '</div>'
            );
        };

        ModalSearch.prototype.printError = function (response) {
            $(".modal-body").empty().append("<h2 style=\"text-align: center;\">Ошибка: " + response.message +
                "<br/> Обратитесь в службу технической поддержки</h2>");
            $(".modal-footer").empty();
        };

        ModalSearch.prototype.printLevels = function (response) {
            $(SELECTORS.LEVEL_BLOCK).empty();
            $.each(response.data, function (number, item) {
                $(SELECTORS.LEVEL_BLOCK).append(
                    '<div style="cursor:pointer;color:#174c8d;background-color:white;" ' +
                    'class="item btn-sm' + (item.id === 0 ? ' bg-primary' : '') + '" data-id="' + item.id + '">' +
                    '<span>' + item.level +
                    '</span>' +
                    '</div>');

                $(SELECTORS.LEVEL_BLOCK).find('[data-id="' + item.id + '"]').click({item: item}, function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    $(SELECTORS.LEVEL_BLOCK).attr('data-id', $(e.currentTarget).attr('data-id'));

                    if (!$(this).hasClass('bg-primary')) {
                        $(this).addClass('bg-primary');
                        $(this).siblings().removeClass('bg-primary');
                    }

                    $(SELECTORS.START_SEARCH).trigger('click');
                });
            });

            $(SELECTORS.LEVEL_BLOCK).prepend(
                '</br><div ' + 'class="btn-sm">' +
                '<h5>Категории</h5>' +
                '</div>'
            );
        };

        ModalSearch.prototype.getAjaxCall = function (methodname, args, callback) {
            return ajax.call([
                {
                    methodname: methodname,
                    args,
                }
            ])[0].then(function (response) {
                return response;
            }).done(function (response) {
                let body = JSON.parse(response['body']);
                if (body["meta"] !== undefined && body["meta"]["success"] !== undefined && body["meta"]["success"] !== false) {
                    callback(body);
                } else {
                    ModalSearch.prototype.printError(body);
                }
                return true;
            }).fail(function () {
                return false;
            });
        };

        ModalSearch.prototype.preloadModalData = function () {
            let categoryId = [null];
            let args = {
                categoryId: categoryId
            };
            ModalSearch.prototype.getAjaxCall('mod_rki_category_tree', args, function (response) {
                ModalSearch.prototype.printCategories(response);

                ModalSearch.prototype.getAjaxCall('mod_rki_level_tree', args, function (response) {
                    ModalSearch.prototype.printLevels(response);
                    $(SELECTORS.START_SEARCH).trigger('click');
                });
            });
        };

        ModalSearch.prototype.resetPagination = function () {
            let maxPage = parseInt($(SELECTORS.CONTENT_BLOCK).attr('data-page'));
            $(SELECTORS.BOOK_PAGINATION).find('a.prev').closest('li.page-item').addClass('disabled');
            $(SELECTORS.BOOK_PAGINATION).find('a.prev').closest('li.page-item').attr('data-page', 0);
            $(SELECTORS.BOOK_PAGINATION).find('a.active').closest('li.page-item').attr('data-page', 1);
            $(SELECTORS.BOOK_PAGINATION).find('a.next').closest('li.page-item').attr('data-page', 2);
            if (maxPage > 1) {
                $(SELECTORS.BOOK_PAGINATION).find('a.active').text(1 + ' из ' + maxPage);
                $(SELECTORS.BOOK_PAGINATION).find('a.next').closest('li.page-item').removeClass('disabled');
            } else {
                $(SELECTORS.BOOK_PAGINATION).find('a.active').text(maxPage + ' из ' + maxPage);
                $(SELECTORS.BOOK_PAGINATION).find('a.next').closest('li.page-item').addClass('disabled');
            }
        };

        ModalRegistry.register(ModalSearch.TYPE, ModalSearch, 'mod_rki/modal_search');

        return ModalSearch;
    });