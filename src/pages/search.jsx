import React from "react";
import Block from "../components/Block";
import * as BS from "react-bootstrap";
import ReactPaginate from 'react-paginate';
import * as Icon from 'react-feather';
import CockpitCheck from '../lib/CockpitCheck';
import CheckHiboutik from '../components/CheckHiboutik';
import TestCredentials from '../components/TestCredentials';
import IsImporting from '../components/IsImporting';

class Search extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            import_status: "",
            formStatus: "",
            hiboutikConnected: false,
            importBtnDisabled: true,
            importSuccess: false,
            importRetry: false,
            importSent: false,
            products: [],
            productPictures: [],
            isImporting: false,
            currentItems: [],
            pageCount: 0,
            searchPageCount: 0,
            productOffset: 0,
            searchProductOffset: 0,
            currentProducts: [],
            search_status: "",
            searched_products: [],
            productsToImport: [],
            searchType: "via-name",
            searchPlaceholder: niwhiboutik_po.by_name,
            search_started: false,
            products_import_status: [],
            importedProducts: [],
            isStopping: false,
            searchCurrentProducts: [],
            importSingleProduct: false,
            import_percentage: 0,
            import_notice: '',
            search_query: '',
            searchType: "via-name",
            search_returned: false,
            isTesting: true
        };

        const checks = new CockpitCheck();
        this.wp = checks.get_wp_api();

        const tests = new TestCredentials();
        tests.testHiboutik().then(response => {
            if (response === true) {
                this.isImporting = new IsImporting();
                this.isImporting.check().then(result => {
                    if (result) {
                        this.setState({
                            importBtnDisabled: true,
                            importAlert: (
                                <BS.Alert variant="danger">
                                    <Icon.AlertOctagon/>
                                    {niwhiboutik_po.status_messages.import_in_progress}
                                </BS.Alert>
                            ),
                            isTesting: false
                        });
                    } else {
                        this.setState({
                            importBtnDisabled: false,
                            isTesting: false,
                            isImporting: false
                        });
                    }
                });
            } else {
                this.setState({
                    importBtnDisabled: false,
                    isTesting: false,
                    isImporting: false
                });
            }
        })

        this.handleSearchPageClick = this.handleSearchPageClick.bind(this);
        this.live_search_products = this.live_search_products.bind(this);
        this.stop_import = this.stop_import.bind(this);
        this.importSingleProduct = this.importSingleProduct.bind(this);
        this.handleSearchType = this.handleSearchType.bind(this);
    }

    set_search_pagination() {
        let endOffset = this.state.searchProductOffset + 15;
        let products = this.state.searched_products.sort((a, b) => (a.product_id > b.product_id) ? 1 : -1);
        let productsToShow = products.slice(this.state.searchProductOffset, endOffset);

        this.setState({
            searchCurrentProducts: productsToShow,
            searchPageCount: Math.ceil(this.state.searched_products.length / 15)
        })
    }

    handleSearchPageClick(e) {
        let offset = e.selected * 15;
        let endOffset = offset + 15;

        let products = this.state.searched_products.sort((a, b) => (a.product_id > b.product_id) ? 1 : -1);

        let productsToShow = products.slice(offset, endOffset);

        this.setState({
            searchCurrentPage: e.selected + 1,
            searchProductOffset: offset,
            searchCurrentProducts: productsToShow,
            searchPageCount: Math.ceil(this.state.searched_products.length / 15)
        });

        setTimeout(() => {
            this.updateSingleProductImportStatus();
        }, 500);

    }

    async import_status() {
        this.get_import_status();

        setInterval(() => {
            this.get_import_status();
        }, 300);
    }

    get_import_status() {
        this.wp.get('/get-import-status').then(response => {
            if (response.status === 200) {
                if (response.data != '') {
                    let import_status;
                    import_status = (
                        <BS.Alert variant="info"><Icon.Info/>{response.data}</BS.Alert>
                    );

                    this.setState({
                        import_status: import_status,
                    });
                }
            }
        })
    }

    stop_import() {
        this.setState({
            isStopping: true,
            isImporting: false,
            importElement: niwhiboutik_po.status_messages.stopping_import,
        });

        this.wp.get('/stop-import').then(response => {
            if (response.status === 200) {
                if (response.data === true) {
                    setTimeout(() => {
                        this.setState({
                            import_status: <BS.Alert
                                variant="info"><Icon.Info/>{niwhiboutik_po.status_messages.import_stopped}.</BS.Alert>,
                            importElement: niwhiboutik_po.status_messages.import_stopped,
                            importSuccess: true,
                            isStopping: false,
                        });

                        setTimeout(() => {
                            this.setState({
                                import_status: "",
                                importSuccess: false,
                                importSent: false,
                                importRetry: false,
                            });
                        }, 3000);
                    }, 1500);
                    ;
                } else {
                    console.error(response.data);
                    this.setState({
                        import_status: <BS.Alert
                            variant="danger"><Icon.XCircle/>{niwhiboutik_po.status_messages.cannot_stop_import}.</BS.Alert>,
                        importElement: niwhiboutik_po.status_messages.cannot_stop_import,
                        importSuccess: false,
                        isStopping: false,
                    });
                }
            } else {
                console.error(response);
                this.setState({
                    import_status: <BS.Alert
                        variant="danger"><Icon.XCircle/>{niwhiboutik_po.status_messages.cannot_stop_import}.</BS.Alert>,
                    isImporting: false,
                    importSuccess: false
                });
            }
        }).catch(error => {
            console.error(error);
        });
    }

    isValid() {
        return this.state.validated;
    }

    handleInputChange(e) {
        this.setState({
            [e.target.name]: e.target.value
        });
    }

    live_search_products(e) {
        if (typeof e !== 'undefined' && typeof e.target !== 'undefined' && typeof e.target.value !== 'undefined') {
            this.setState({
                search_query: e.target.value
            });
        }

        this.setState({
            search_started: true
        });

        setTimeout(() => {
            if (typeof this.state.search_query !== "undefined" && this.state.search_query != "" && this.state.search_query.length >= 3) {
                this.start_search();
            } else if (typeof this.state.search_query !== "undefined" && this.state.search_query != "" && this.state.searchType === "via-id") {
                this.start_search();
            } else if (typeof this.state.search_query !== "undefined" && this.state.search_query != "" && typeof e.target !== "undefined" && e.target.value === "") {
                this.setState({
                    search_started: false,
                    search_status: "",
                    searched_products: []
                });
            } else if (typeof this.state.search_query !== "undefined" && this.state.search_query === "") {
                this.setState({
                    search_started: false,
                    search_status: "",
                    searched_products: []
                });
            } else if (this.state.searchType !== "via-id") {
                this.setState({
                    search_started: false,
                    search_status: <BS.Alert variant="info">{niwhiboutik_po.please.three_characters}.</BS.Alert>,
                    searched_products: []
                });
            }
        }, 500);
    }


    start_search() {
        this.setState({
            search_started: true,
            search_returned: false,
            searched_products: [],
            search_status: <BS.Alert variant="info"><Icon.Search/>
                {niwhiboutik_po.search_in_progress}
                <BS.Spinner
                    className="ms-1"
                    as="span"
                    animation="border"
                    size="sm"
                    role="status"
                    aria-hidden="true"
                />
            </BS.Alert>
        });

        this.wp.post('/search-products', {
            search_query: this.state.search_query,
            search_type: this.state.searchType
        }).then(response => {
            if (response.status === 200) {
                let message = "";

                if (response.data.length === 1) {
                    message = (
                        <BS.Alert variant="success"><Icon.CheckCircle/>
                            {response.data.length} {niwhiboutik_po.product_found}
                        </BS.Alert>
                    )
                } else if (response.data.length > 1) {
                    message = (
                        <BS.Alert variant="success"><Icon.CheckCircle/>
                            {response.data.length} {niwhiboutik_po.products_found}
                        </BS.Alert>
                    )
                } else if (response.data.length === 0) {
                    message = "";
                } else {
                    message = "";
                }

                this.setState({
                    searched_products: response.data,
                    search_status: message,
                    search_returned: true,
                });

                setTimeout(() => {
                    this.set_search_pagination()
                }, 500);
            } else {
                console.error(response);
                this.setState({
                    search_status: <BS.Alert variant="danger"><Icon.XCircle/>
                        {niwhiboutik_po.search_error}
                    </BS.Alert>,
                });
            }

        }).catch(error => {
            console.error(error);
            this.setState({
                search_status: <BS.Alert variant="danger"><Icon.XCircle/>{niwhiboutik_po.search_error}</BS.Alert>,
                search_started: false,
            });
        })
    }

    updateSingleProductImportStatus() {
        let productsToImport = document.querySelectorAll('.product-to-import');

        if (productsToImport.length > 0) {
            for (let i = 0; i < productsToImport.length; i++) {
                const button = productsToImport[i].getElementsByTagName('button')[0];
                if (productsToImport[i].classList.contains('is-importing')) {
                    button.innerHTML = niwhiboutik_po.status_messages.importing + "...";
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-secondary');
                } else if (productsToImport[i].classList.contains('is-imported')) {
                    button.innerHTML = niwhiboutik_po.status_messages.import_done;
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-success');

                    setTimeout(() => {
                        button.innerHTML = niwhiboutik_po.import;
                        button.classList.remove('btn-success');
                        button.classList.add('btn-primary');
                    }, 5000);
                } else if (productsToImport[i].classList.contains('has-failed')) {
                    button.innerHTML = niwhiboutik_po.status_messages.error;
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-danger');

                    setTimeout(() => {
                        button.innerHTML = niwhiboutik_po.status_messages.retry;
                        button.classList.remove('btn-success');
                        button.classList.add('btn-primary');
                    }, 5000);
                } else {
                    button.innerHTML = niwhiboutik_po.import;
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-primary');
                }
            }
        }
    }

    importSingleProduct(e) {
        this.setState({
            isImporting: true,
            importSingleProduct: true,
            importElement: niwhiboutik_po.importing_product + " #" + e.target.dataset.product_id + " "
        });

        this.import_status();

        let productIteration;

        if (typeof e.target !== "undefined") {
            productIteration = e.target.parentNode;
        } else {
            productIteration = e;
        }

        productIteration.classList.add('is-importing');
        this.updateSingleProductImportStatus();

        this.wp.post('/import-product', {
            product_id: e.target.dataset.product_id
        }).then(response => {
            this.setState({
                isImporting: false,
            });

            if (response.status === 200) {
                if (typeof response.data.error !== 'undefined') {
                    console.error(response.data.error);

                    this.stop_import().then(status => {
                        if (status === true) {
                            this.setState({
                                isImporting: false,
                                importSingleProduct: false,
                                import_status: <BS.Alert variant="danger"><Icon.XCircle/>
                                    {niwhiboutik_po.error_importing_product} #{e.target.dataset.product_id}
                                </BS.Alert>
                            });
                        }
                    })
                } else {
                    this.setState({
                        isImporting: false,
                    });

                    this.wp.get('/clear-import-status').then(response => {
                        if (response.status === 200) {
                            productIteration.classList.remove('is-importing');
                            productIteration.classList.add('is-imported');

                            this.setState({
                                import_status: <BS.Alert variant="success"><Icon.CheckCircle/>
                                    {niwhiboutik_po.product} #{e.target.dataset.product_id} {niwhiboutik_po.was_imported}
                                </BS.Alert>,
                                importSingleProduct: false,
                            });

                            setTimeout(() => {
                                productIteration.classList.remove('is-imported');
                                this.updateSingleProductImportStatus();
                            }, 100);

                            setTimeout(() => {
                                this.setState({
                                    import_status: ""
                                });
                            }, 5000);
                        } else {
                            this.setState({
                                import_status: <BS.Alert variant="danger"><Icon.XCircle/>
                                    {niwhiboutik_po.the_product} #{e.target.dataset.product_id} {niwhiboutik_po.was_not_imported}
                                </BS.Alert>,
                                importSingleProduct: false,
                            });

                            setTimeout(() => {
                                productIteration.classList.remove('is-importing');
                                productIteration.classList.add('has-failed');
                                this.setState({
                                    import_status: ""
                                });
                                this.updateSingleProductImportStatus();
                            }, 100);

                            setTimeout(() => {
                                this.setState({
                                    import_status: ""
                                });
                            }, 5000);
                            console.error(response);
                        }
                    })
                }
            } else {
                console.error(response);
                this.setState({
                    isImporting: false,
                    import_status: <BS.Alert variant="danger"><Icon.XCircle/>
                        {niwhiboutik_po.error_importing_product} #{e.target.dataset.product_id}
                    </BS.Alert>,
                    importRetry: true,
                });

                setTimeout(() => {
                    productIteration.classList.remove('is-importing');
                    productIteration.classList.add('has-failed');
                    this.setState({
                        import_status: ""
                    });
                    this.updateSingleProductImportStatus();
                }, 5000);
            }
        }).catch(error => {
            console.error(error);
            this.setState({
                import_status: <BS.Alert variant="danger"><Icon.XCircle/>
                    {niwhiboutik_po.error_importing_product}
                </BS.Alert>
            });

            setTimeout(() => {
                productIteration.classList.remove('is-importing');
                productIteration.classList.add('has-failed');
                this.setState({
                    import_status: ""
                });
                this.updateSingleProductImportStatus();
            }, 5000);
        });
    }

    handleSearchType(e) {
        if (e.target.dataset.search_type === "via-name") {
            this.setState({
                searchPlaceholder: niwhiboutik_po.by_name,
                searchType: 'via-name'
            })
        } else if (e.target.dataset.search_type === "via-id") {
            this.setState({
                searchPlaceholder: niwhiboutik_po.by_id,
                searchType: 'via-id'
            })
        }

        this.setState({
            searchType: e.target.dataset.search_type,
        });

        setTimeout(() => {
            this.live_search_products();
        }, 500);
    }

    render() {
        return (
            <div className="container">
                <div>
                    <h1>{niwhiboutik_po.search}</h1>
                </div>
                {this.state.importAlert}
                <CheckHiboutik/>
                {
                    this.state.import_status !== "" ?
                        <Block>
                            <BS.Row className="mt-3">
                                <BS.Col>
                                    <h2>{niwhiboutik_po.import_status}</h2>
                                    {this.state.import_status}
                                </BS.Col>
                            </BS.Row>
                        </Block>
                        : null
                }
                <Block>
                    <BS.Row>
                        <BS.Col>
                            <h2>
                                {niwhiboutik_po.search_products_to_import.title}
                            </h2>
                        </BS.Col>
                    </BS.Row>
                    <BS.Row>
                        <BS.Col>
                            <p>
                                {niwhiboutik_po.search_products_to_import.description}
                            </p>
                        </BS.Col>
                    </BS.Row>
                    <BS.Row>
                        <BS.Col>
                            <BS.Form className="mt-3">
                                {
                                    this.state.isTesting ?
                                        <BS.Button variant='primary' disabled className="w-100" type="submit"
                                                   id="submit-new-project">
                                            {niwhiboutik_po.status_messages.check_in_progress}
                                            <BS.Spinner
                                                as="span"
                                                animation="border"
                                                size="sm"
                                                role="status"
                                                aria-hidden="true"
                                                className="ms-1"
                                            />
                                            <span
                                                className="visually-hidden">{niwhiboutik_po.status_messages.loading}...</span>
                                        </BS.Button>
                                        :
                                        <BS.InputGroup className="mb-3 search-form">
                                            <BS.DropdownButton
                                                variant="primary"
                                                title={niwhiboutik_po.search + " " + this.state.searchPlaceholder}
                                                id="search-product"
                                                align="start"
                                                disabled={this.state.importBtnDisabled}
                                            >
                                                <BS.Dropdown.Item onClick={this.handleSearchType}
                                                                  data-search_type="via-name">{niwhiboutik_po.search_by_name}</BS.Dropdown.Item>
                                                <BS.Dropdown.Item onClick={this.handleSearchType}
                                                                  data-search_type="via-id">{niwhiboutik_po.search_by_id}</BS.Dropdown.Item>
                                            </BS.DropdownButton>
                                            <BS.Form.Control placeholder={niwhiboutik_po.search_products}
                                                             disabled={this.state.importBtnDisabled}
                                                             aria-label={niwhiboutik_po.search_products}
                                                             aria-describedby="search-product" name="search_product"
                                                             onChange={this.live_search_products}/>
                                        </BS.InputGroup>
                                }
                            </BS.Form>
                            {this.state.search_status !== "" ? this.state.search_status : null}
                            {
                                this.state.search_started === true && this.state.searched_products.length > 0
                                    ?
                                    <div className="d-flex flex-column align-items-center justify-content-center">
                                        <div className="w-100">
                                            {
                                                this.state.searchCurrentProducts.map((product, index) => {
                                                    return (
                                                        <div key={index} id={product.product_id}
                                                             className="alert alert-info d-flex align-items-center justify-content-between product-to-import">
                                                            <p className="mb-0">
                                                                <b>#{product.product_id}</b> - {product.product_model}
                                                            </p>
                                                            <BS.Button disabled={this.state.isImporting}
                                                                       onClick={this.importSingleProduct} size="sm"
                                                                       data-product_id={product.product_id}>{niwhiboutik_po.import}</BS.Button>
                                                        </div>
                                                    )
                                                })
                                            }
                                        </div>
                                        <div className="mt-3"></div>
                                        {this.state.searched_products.length > 15 ?
                                            <ReactPaginate
                                                className="pagination"
                                                pageClassName="page-item"
                                                pageLinkClassName="page-link"
                                                activeClassName="active"
                                                nextClassName="page-item"
                                                previousClassName="page-item"
                                                nextLinkClassName="page-link"
                                                breakClassName="page-item"
                                                breakLinkClassName="page-link"
                                                previousLinkClassName="page-link"
                                                breakLabel="..."
                                                nextLabel="&raquo;"
                                                onPageChange={this.handleSearchPageClick}
                                                pageRangeDisplayed={3}
                                                pageCount={this.state.searchPageCount}
                                                previousLabel="&laquo;"
                                                renderOnZeroPageCount={null}
                                            />
                                            : null}
                                    </div>
                                    :
                                    this.state.search_started === true && this.state.search_returned === true && this.state.search_query.length >= 3 && this.state.searched_products.length === 0
                                        ?
                                        <BS.Alert variant="warning">
                                            <Icon.XCircle/>
                                            {niwhiboutik_po.no_results}
                                        </BS.Alert>
                                        :
                                        null
                            }
                        </BS.Col>
                    </BS.Row>
                </Block>
            </div>
        );
    }
}

export default Search;
