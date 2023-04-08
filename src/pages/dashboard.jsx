import React from "react";
import Block from "../components/Block";
import * as BS from "react-bootstrap";
import * as Icon from 'react-feather';
import CockpitCheck from '../lib/CockpitCheck';
import CheckHiboutik from '../components/CheckHiboutik';
import TestCredentials from '../components/TestCredentials';
import IsImporting from '../components/IsImporting';
import Swal from 'sweetalert2';

class Dashboard extends React.Component
{
    constructor(props)
    {
        super(props);

        this.state = {
            import_status: "",
            hiboutikConnected: false,
            importBtnDisabled: true,
            saveBtnDisabled: true,
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
            search_started: false,
            products_import_status: [],
            importedProducts: [],
            isStopping: false,
            searchCurrentProducts: [],
            importSingleProduct: false,
            import_percentage: 0,
            import_notice: '',
            schedule: '',
            isTesting: true,
            import_images: true,
            refreshBtnContents: niwhiboutik_po.refresh,
            refreshBtnDisabled: false,
            regenerateBtnContents: niwhiboutik_po.menu_options.regenerate,
            regenerateBtnDisabled: false,
            regenerateStatus: null,
            menu_include_shop: true,
        };

        const checks = new CockpitCheck();
        this.wp = checks.get_wp_api();

        const tests = new TestCredentials();
        tests.testHiboutik().then(response =>
        {
            if (response === true)
            {
                this.isImporting = new IsImporting();
                this.isImporting.check().then(result =>
                {
                    if (result === true)
                    {
                        this.import_status();
                        this.setState({
                            isImporting: true,
                            importSent: true,
                            importElement: niwhiboutik_po.status_messages.importing,
                            isTesting: false
                        });
                    }
                    else
                    {
                        this.setState({
                            isTesting: false,
                            importBtnDisabled: false
                        });
                    }
                }).catch(error =>
                {
                    console.error(error);
                });

            }
            else
            {
                this.setState({
                    isTesting: false,
                    importBtnDisabled: true
                });
            }
        })

        this.importCatalog = this.importCatalog.bind(this);
        this.importAllproducts = this.importAllproducts.bind(this);
        this.stop_import = this.stop_import.bind(this);
        this.handleInputChange = this.handleInputChange.bind(this);
        this.refresh_status = this.refresh_status.bind(this);
        this.regenerate_menu = this.regenerate_menu.bind(this);
    }

    regenerate_menu()
    {
        this.setState({
            regenerateBtnContents: (
                <>
                    <span className="me-2">{niwhiboutik_po.status_messages.regenerating}</span>
                    <BS.Spinner animation="border" size="sm" role="status" aria-hidden="true" />
                </>
            ),
            regenerateBtnDisabled: true
        });

        this.wp.post('/regenerate-menu', {
            include_shop: this.state.menu_include_shop
        }).then(response =>
        {
            if (response.data === true)
            {
                this.setState({
                    regenerateStatus: (
                        <BS.Alert variant="success">
                            {niwhiboutik_po.status_messages.regenerated}
                        </BS.Alert>
                    ),
                    regenerateBtnContents: niwhiboutik_po.menu_options.regenerate,
                    regenerateBtnDisabled: false
                });
            }
        });
    }

    checkIsImporting()
    {
        return new Promise((resolve, reject) =>
        {
            this.isImporting.check().then(status =>
            {
                if (status === true)
                {
                    console.log("Importing");
                    resolve(true);
                }
                else
                {
                    setTimeout(() =>
                    {
                        resolve(this.checkIsImporting());
                    }, 1000);
                }
            }).catch(error =>
            {
                reject(error);
            });
        });
    }

    refresh_status()
    {
        this.setState({
            refreshBtnContents: (
                <BS.Spinner animation="border" size="sm" role="status" aria-hidden="true" />
            ),
            refreshBtnDisabled: true
        });

        this.import_status();

        setTimeout(() =>
        {
            this.setState({
                refreshBtnContents: niwhiboutik_po.refresh,
                refreshBtnDisabled: false
            });
        }, 2000);

    }

    async import_status()
    {
        this.get_import_status();
        setInterval(() =>
        {
            this.get_import_status();
        }, 5000);
    }

    get_import_status()
    {
        try
        {
            this.wp.get('/get-import-status').then(response =>
            {
                if (response.status === 200)
                {
                    if (response.data != '')
                    {
                        let import_status;
                        let import_percentage;
                        if (response.data.includes('|'))
                        {
                            let import_status_array = response.data.split('|');
                            import_percentage = import_status_array[0];

                            if (typeof import_percentage !== "undefined" && import_percentage !== null && import_percentage != "")
                            {
                                let variant = "primary";
                                let animated = true;

                                if (import_percentage < 100)
                                {
                                    variant = "info";
                                    animated = true;
                                }
                                else if (import_percentage === 100)
                                {
                                    variant = "success";
                                    animated = false;
                                }

                                import_status = (
                                    <>
                                        <div className="d-flex justify-content-between import-status">
                                            <p className="mb-0 pb-0 mt-2">{niwhiboutik_po.progress}: {import_percentage}%
                                                <br />
                                                {niwhiboutik_po.remaining_time}: {import_status_array[3]} minutes
                                            </p>
                                            <p className="mb-0 pb-0 mt-2 text-end">
                                                <i>{import_status_array[2]}</i>
                                                <br />
                                                {import_status_array[4]}
                                                <br />
                                                <b>{import_status_array[1]}</b>
                                            </p>
                                        </div>
                                        <BS.ProgressBar variant={variant} animated={animated} now={import_percentage} />
                                    </>
                                );
                            }
                        }
                        else
                        {
                            import_percentage = 0;
                            import_status = (
                                <BS.Alert variant="info"><Icon.Info />{response.data}</BS.Alert>
                            );
                        }

                        this.setState({
                            import_status: import_status,
                            import_percentage: import_percentage
                        });
                    }
                }
                else
                {
                    console.error(response);
                }
            });
        }
        catch (error)
        {
            console.log('There was an error fetching the import status.');
            console.error(error);
        }
    }

    stop_import()
    {
        this.setState({
            isStopping: true,
            isImporting: false,
            importElement: niwhiboutik_po.status_messages.stopping_import,
        });

        this.wp.get('/stop-import').then(response =>
        {
            if (response.status === 200)
            {
                if (response.data === true)
                {
                    setTimeout(() =>
                    {
                        this.setState({
                            import_status: <BS.Alert
                                variant="info"><Icon.Info />{niwhiboutik_po.status_messages.import_stopped}</BS.Alert>,
                            importElement: niwhiboutik_po.status_messages.import_stopped,
                            importSuccess: true,
                            isStopping: false,
                            isImporting: false,
                            import_percentage: 0
                        });

                        setTimeout(() =>
                        {
                            this.setState({
                                import_status: "",
                                importSuccess: false,
                                importSent: false,
                                importRetry: false,
                            });
                        }, 3000);
                    }, 1500);
                }
                else
                {
                    console.error(response.data);
                    this.setState({
                        import_status: <BS.Alert
                            variant="danger"><Icon.XCircle />{niwhiboutik_po.status_messages.cannot_stop_import}.</BS.Alert>,
                        importElement: niwhiboutik_po.status_messages.cannot_stop_import,
                        importSuccess: false,
                        isStopping: false,
                    });
                }
            }
            else
            {
                console.error(response);
                this.setState({
                    import_status: <BS.Alert
                        variant="danger"><Icon.XCircle />{niwhiboutik_po.status_messages.cannot_stop_import}.</BS.Alert>,
                    isImporting: false,
                    importSuccess: false
                });
            }
        }).catch(error =>
        {
            console.error(error);
        });
    }

    importCatalog(e)
    {
        e.preventDefault();

        Swal.fire({
            title: niwhiboutik_po.update_catalog.title,
            text: niwhiboutik_po.update_catalog.description,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: niwhiboutik_po.import_catalog.title
        }).then((result) =>
        {
            if (result.isConfirmed)
            {
                this.importAllproducts();
                Swal.fire({
                    title: niwhiboutik_po.alright,
                    text: niwhiboutik_po.import_starting_background,
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                });
            }
            else
            {
                Swal.fire({
                    title: niwhiboutik_po.alright,
                    text: niwhiboutik_po.import_canceled,
                    icon: 'error',
                    confirmButtonColor: '#3085d6',

                });
            }
        })

    }

    importAllproducts()
    {
        this.setState({
            import_notice: "",
        });

        this.setState({
            isImporting: true,
            importSent: true,
            importElement: niwhiboutik_po.status_messages.importing,
        });

        setTimeout(() =>
        {
            this.wp.get('/fetch').then(response =>
            {
                if (response.status === 200)
                {
                    this.setState({
                        products: response.data,
                    });

                    setTimeout(() =>
                    {
                        this.import_status().then(r => r);

                        setTimeout(() =>
                        {
                            this.import_status().then(r => r);
                        }, 1000);
                    }, 500);

                    this.wp.post('/import', {
                        import_images: this.state.import_images,
                    }).then(response =>
                    {
                        if (response.status === 200)
                        {
                            this.wp.get('/clear-import-status').then(response =>
                            {
                                if (response.status === 200)
                                {
                                    if (this.state.isStopping === false)
                                    {
                                        setTimeout(() =>
                                        {
                                            this.setState({
                                                importElement: niwhiboutik_po.status_messages.import_finished,
                                                import_status: (
                                                    <>
                                                        <BS.Alert
                                                            variant="success"><Icon.CheckCircle />{niwhiboutik_po.status_messages.import_done}.</BS.Alert>
                                                        <BS.ProgressBar variant="success" label={'100%'} now={100} />
                                                    </>
                                                ),
                                                isImporting: false,
                                                importSuccess: true,
                                                import_percentage: 100,
                                                importRetry: false,

                                            });
                                        }, 1500);
                                    }
                                    else
                                    {
                                        let refreshIsStopping = setInterval(() =>
                                        {
                                            if (this.state.isStopping === false)
                                            {
                                                setTimeout(() =>
                                                {
                                                    this.setState({
                                                        importElement: niwhiboutik_po.status_messages.import_finished,
                                                        import_status: (
                                                            <>
                                                                <BS.Alert
                                                                    variant="success"><Icon.CheckCircle />{niwhiboutik_po.status_messages.import_finished}.</BS.Alert>
                                                                <BS.ProgressBar variant="success" label={'100%'}
                                                                    now={100} />
                                                            </>
                                                        ),
                                                        isImporting: false,
                                                        importSuccess: true,
                                                        import_percentage: 100,
                                                        importRetry: false,
                                                    });

                                                    clearInterval(refreshIsStopping);
                                                }, 1500);
                                            }
                                        }, 1000);
                                    }
                                }
                                else
                                {
                                    console.error(response);
                                    this.setState({
                                        importElement: niwhiboutik_po.status_messages.cannot_retrieve_products,
                                        isImporting: false,
                                        importSuccess: true,
                                    });
                                }
                            });

                        }
                        else
                        {
                            this.setState({
                                importElement: niwhiboutik_po.status_messages.import_error,
                                isImporting: false,
                                importSuccess: false,
                            });
                        }
                    }).catch(error =>
                    {
                        console.error(error);
                        this.setState({
                            importElement: niwhiboutik_po.status_messages.import_error,
                            isImporting: false,
                            importSuccess: false,
                        });
                    });
                }
                else
                {
                    this.setState({
                        importElement: niwhiboutik_po.status_messages.cannot_retrieve_products,
                        isImporting: false,
                        importSuccess: true,
                    });
                }
            });
        }, 500);

    }

    getImportBtnContents(state)
    {
        if (state.isImporting === true)
        {
            return (
                <>
                    <BS.Button variant="primary" disabled className="d-flex align-items-center justify-content-center">
                        <span className="me-2">{this.state.importElement}</span>
                        <BS.Spinner
                            as="span"
                            animation="border"
                            size="sm"
                            role="status"
                            aria-hidden="true"
                        />
                        <span className="visually-hidden">{niwhiboutik_po.status_messages.loading}...</span>
                    </BS.Button>
                    <BS.Button disabled={this.state.isStopping} variant="danger" className="ms-3"
                        onClick={this.stop_import}>
                        <span className="ms-2">
                            {this.state.isStopping ? niwhiboutik_po.status_messages.stopping : niwhiboutik_po.actions.stop_import}
                        </span>
                    </BS.Button>
                </>
            );
        }
        else if (state.importSent === true && state.isImporting === false && state.importSuccess === true)
        {
            return (
                <BS.Button disabled variant='success' type="submit" id="submit-new-project">
                    {this.state.importElement}
                </BS.Button>
            );
        }
        else if (state.isStopping === true)
        {
            return (
                <BS.Button variant='warning' type="submit" id="submit-new-project">
                    {niwhiboutik_po.status_messages.stopping}...
                </BS.Button>
            )
        }
        else if (state.importSent === true && state.importSuccess === false && state.importRetry === false && state.isStopping === false)
        {
            return (
                <BS.Button variant='danger' type="submit" id="submit-new-project">
                    {niwhiboutik_po.status_messages.error}
                </BS.Button>
            )
        }
        else if (state.importSent === true && state.reqSuccess === false && state.retry === false && state.isStopping === true)
        {
            return (
                <BS.Button disabled variant='warning' type="submit" id="submit-new-project">
                    {niwhiboutik_po.status_messages.stopping}...
                </BS.Button>
            )
        }
        else if (state.importSent === true && state.importRetry === true)
        {
            return (
                <BS.Button variant='warning' type="submit" id="submit-new-project">
                    {niwhiboutik_po.status_messages.retry}
                </BS.Button>
            )
        }
        else if (state.isTesting === true)
        {
            return (
                <BS.Button variant='primary' disabled className="w-100" type="submit" id="submit-new-project">
                    {niwhiboutik_po.status_messages.check_in_progress}
                    <BS.Spinner
                        as="span"
                        animation="border"
                        size="sm"
                        role="status"
                        aria-hidden="true"
                        className="ms-1"
                    />
                    <span className="visually-hidden">{niwhiboutik_po.status_messages.loading}...</span>
                </BS.Button>
            )
        }
        else
        {
            return (
                <div className="row d-flex flex-column w-100">
                    <div className="col mb-3">
                        <label className="toggle d-inline-block">
                            <input type="checkbox" name={"import_images"} checked={state.import_images}
                                onChange={this.handleInputChange}
                                className="toggle__input" />
                            <span className="toggle-track">
                                <span className="toggle-indicator">
                                    <span className="checkMark">
                                        <svg viewBox="0 0 24 24" id="ghq-svg-check" role="presentation"
                                            aria-hidden="true">
                                            <path
                                                d="M9.86 18a1 1 0 01-.73-.32l-4.86-5.17a1.001 1.001 0 011.46-1.37l4.12 4.39 8.41-9.2a1 1 0 111.48 1.34l-9.14 10a1 1 0 01-.73.33h-.01z"></path>
                                        </svg>
                                    </span>
                                </span>
                            </span>
                        </label>
                        <p className="mb-0 float-start mt-1 me-3">{niwhiboutik_po.import_images}</p>
                    </div>
                    <div className="col">
                        <BS.Button disabled={this.state.importBtnDisabled} onClick={this.importCatalog}
                            variant='primary' type="button" className='w-100' id="submit-new-project">
                            {niwhiboutik_po.import_catalog.title}
                        </BS.Button>
                    </div>
                </div>
            )
        }
    }

    handleInputChange(e)
    {
        this.setState({
            [e.target.name]: e.target.checked
        });
    }

    render()
    {
        return (
            <div className="container">
                <div>
                    <h1>{niwhiboutik_po.control_panel}</h1>
                </div>
                <CheckHiboutik />
                {
                    this.state.isImporting && this.state.import_status !== "" ?
                        <Block>
                            <BS.Row className="mt-3">
                                <BS.Col>
                                    <BS.Row className="mb-3">
                                        <BS.Col className="d-flex align-items-center">
                                            <h2>{niwhiboutik_po.import_status}</h2>
                                        </BS.Col>
                                        <BS.Col>
                                            <BS.Button disabled={this.state.refreshBtnDisabled} size="sm" variant="info"
                                                className="float-end" onClick={this.refresh_status}>
                                                {this.state.refreshBtnContents}</BS.Button>
                                        </BS.Col>
                                    </BS.Row>
                                    {this.state.import_status}
                                </BS.Col>
                            </BS.Row>
                        </Block>
                        : null
                }
                <Block>
                    <BS.Row>
                        <BS.Col>
                            <h2 className="mb-3">
                                {niwhiboutik_po.import_catalog.title}
                            </h2>
                        </BS.Col>
                    </BS.Row>
                    <BS.Row>
                        <BS.Col>
                            <p>
                                {niwhiboutik_po.import_catalog.description}
                            </p>
                        </BS.Col>
                    </BS.Row>
                    <BS.Row>
                        <BS.Col>
                            <div className="d-flex button-group">
                                {this.getImportBtnContents(this.state)}
                            </div>
                        </BS.Col>
                    </BS.Row>
                </Block>
            </div>
        )
            ;
    }
}

export default Dashboard;
