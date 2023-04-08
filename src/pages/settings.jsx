import React from "react";
import Block from "../components/Block";
import * as BS from "react-bootstrap";
import Axios from "axios";
import Bootstrap from 'bootstrap';
import * as Icon from 'react-feather';
import CockpitCheck from '../lib/CockpitCheck';
import IsImporting from '../components/IsImporting';
import Swal from 'sweetalert2';

class Settings extends React.Component
{
    constructor(props)
    {
        super(props);

        this.loadingBtn = (
            <BS.Button variant="primary" disabled>
                <BS.Spinner
                    as="span"
                    animation="border"
                    size="sm"
                    role="status"
                    aria-hidden="true"
                />
                <span className="visually-hidden">{niwhiboutik_po.status_messages.loading}...</span>
            </BS.Button>
        );

        this.state = {
            validated: false,
            hiboutikStatus: <BS.Badge bg="primary">{niwhiboutik_po.status_messages.loading}...</BS.Badge>,
            hiboutik_link: "",
            hiboutik_login: "",
            hiboutik_key: "",
            importAlert: "",
            byniwee_status: <BS.Badge bg="primary">{niwhiboutik_po.status_messages.loading}...</BS.Badge>,
            license: "",
            credentialsBtnContents: this.loadingBtn,
            byniweeBtnContents: this.loadingBtn,
            activationBtnContents: "",
            hiboutikFormDisabled: true,
            byniweeFormDisabled: true,
            license_field_type: "text",
            license_activated: false,
            is_activated: false
        };

        this.submitHiboutikParams = this.submitHiboutikParams.bind(this);
        this.handleInputChange = this.handleInputChange.bind(this);
        this.submitByniweeKey = this.submitByniweeKey.bind(this);
        this.submitByniweeActivation = this.submitByniweeActivation.bind(this);

        const checks = new CockpitCheck();
        this.wp = checks.get_wp_api();

        this.wp.get('/is-activated').then(response =>
        {
            this.setState({
                is_activated: response.data
            });

            if (response.data === true)
            {
                setTimeout(() =>
                {
                    this.setState({
                        hiboutikStatus: <BS.Badge bg="primary">Getting hiboutik info...</BS.Badge>,
                    });
                }, 1000);

                this.getHiboutikInfo().then(status =>
                {
                    if (status === true)
                    {
                        this.setState({
                            hiboutikStatus: <BS.Badge bg="primary">Testing hiboutik login...</BS.Badge>,
                        });
                        setTimeout(() =>
                        {
                            this.testHiboutik().then(status =>
                            {
                                if (status)
                                {
                                    const isImporting = new IsImporting();
                                    isImporting.check().then(result =>
                                    {
                                        if (result)
                                        {
                                            this.setState({
                                                importAlert: (
                                                    <BS.Alert variant="danger">
                                                        <Icon.AlertOctagon />
                                                        {niwhiboutik_po.status_messages.settings_import_in_progress}
                                                    </BS.Alert>
                                                ),
                                                hiboutikStatus: <BS.Badge bg="danger">{niwhiboutik_po.status_messages.settings_import_in_progress}</BS.Badge>,
                                                byniwee_status: <BS.Badge bg="danger">{niwhiboutik_po.status_messages.settings_import_in_progress}</BS.Badge>,
                                                credentialsBtnContents: (
                                                    <BS.Button disabled variant='primary' type="submit" id="submit-hiboutik-credentials">
                                                        {niwhiboutik_po.status_messages.cannot_save_while_importing}
                                                    </BS.Button>
                                                ),
                                                hiboutikFormDisabled: true,
                                            });
                                        }
                                        else
                                        {
                                            if (this.state.hiboutik_link !== "" && this.state.hiboutik_login !== "" && this.state.hiboutik_key !== "")
                                            {
                                                this.setState({
                                                    credentialsBtnContents: (
                                                        <BS.Button variant='primary' type="submit" id="submit-hiboutik-credentials">
                                                            {niwhiboutik_po.status_messages.update}
                                                        </BS.Button>
                                                    ),
                                                    hiboutikFormDisabled: false,
                                                });
                                            }
                                            else
                                            {
                                                this.setState({
                                                    importAlert: "",
                                                    credentialsBtnContents: (
                                                        <BS.Button variant='primary' type="submit" id="submit-hiboutik-credentials">
                                                            {niwhiboutik_po.status_messages.save}
                                                        </BS.Button>
                                                    ),
                                                    hiboutikFormDisabled: false,
                                                });
                                            }
                                        }
                                    });
                                }
                            })
                        }, 1000);
                    }
                    else
                    {
                        this.setState({
                            hiboutikStatus: <BS.Badge bg="danger">{niwhiboutik_po.status_messages.error_retrieving_information}.</BS.Badge>,
                        });
                    }
                });
            }
        }).catch(error =>
        {
            console.error(error);
        });

        setTimeout(() =>
        {
            this.setState({
                byniwee_status: <BS.Badge bg="primary">Getting license...</BS.Badge>,
                license_status: null
            });
        }, 1000);

        this.getByniweeLicense().then(status =>
        {
            this.setLicenseStatus(status);
        }).catch(error =>
        {
            this.setState({
                byniwee_status: <BS.Badge bg="danger">{niwhiboutik_po.status_messages.error}.</BS.Badge>,
            })
            console.error(error);
        });
    }

    setLicenseStatus(status)
    {
        if (status === false)
        {
            this.setState({
                byniweeBtnContents: (
                    <BS.Button disabled variant='primary' type="submit" id="submit-byniwee-credentials">
                        {niwhiboutik_po.status_messages.error}
                    </BS.Button>
                ),
                byniweeFormDisabled: true,
                byniwee_status: <BS.Badge bg="danger">{niwhiboutik_po.status_messages.error}</BS.Badge>,
            });
        }
        else if (status.status && status.status === "error")
        {
            this.setState({
                byniwee_status: <BS.Badge bg="danger">{status.message}</BS.Badge>,
                byniweeBtnContents: (
                    <BS.Button variant='danger' disabled type="submit" id="submit-byniwee-credentials">
                        {niwhiboutik_po.status_messages.error}
                    </BS.Button>
                ),
                byniweeFormDisabled: true,
            });
        }
        else if (status.status && status.status === "info")
        {
            this.setState({
                byniwee_status: <BS.Badge bg="success">{status.message}</BS.Badge>,
                byniweeBtnContents: (
                    <BS.Button variant='primary' type="submit" id="submit-byniwee-credentials">
                        {niwhiboutik_po.status_messages.save}
                    </BS.Button>
                ),
                license_field_type: "text",
                byniweeFormDisabled: false,
            });

            if (status.license)
            {
                this.setState({
                    license: status.license,
                });
            }
        }
        else if (status.status && status.status === "warning")
        {
            this.setState({
                byniwee_status: <BS.Badge bg="danger">{status.message}</BS.Badge>,
                byniweeBtnContents: (
                    <BS.Button variant='primary' type="submit" id="submit-byniwee-credentials">
                        {niwhiboutik_po.status_messages.save}
                    </BS.Button>
                ),
                license_field_type: "text",
                byniweeFormDisabled: false,
            });

            if (status.license)
            {
                this.setState({
                    license: status.license,
                });
            }
        }
        else if (status.status && status.status === "success" && status.activated && status.activated === true)
        {
            this.setState({
                byniwee_status: <BS.Badge bg="success">{status.message}</BS.Badge>,
                byniweeBtnContents: (
                    <BS.Button variant='primary' type="submit" id="submit-byniwee-credentials">
                        {niwhiboutik_po.status_messages.update}
                    </BS.Button>
                ),
                license: status.license,
                byniweeFormDisabled: false,
                license_activated: true,
            });

            this.getLicenseInfo();
        }
        else if (status.status && status.status === "success" && status.activated === false)
        {
            this.setState({
                byniwee_status: <BS.Badge bg="success">{status.message}</BS.Badge>,
                license_status: (
                    <BS.Alert variant="warning">{niwhiboutik_po.status_messages.please_activate}</BS.Alert>
                ),
                license: status.license,
                byniweeFormDisabled: false,
                byniweeBtnContents: (
                    <BS.Button variant='primary' type="submit" id="submit-byniwee-credentials">
                        {niwhiboutik_po.status_messages.update}
                    </BS.Button>
                ),
                activationBtnContents: (
                    <a className="btn btn-success w-100 mt-3" href="https://plugins.byniwee.io/mon-compte/view-license-keys/" target="_blank" onClick={this.submitByniweeActivation}>
                        {niwhiboutik_po.status_messages.activate}
                    </a>
                )
            });

            this.getLicenseInfo();
        }
    }

    submitByniweeActivation(e)
    {
        e.preventDefault();

        this.setState({
            byniwee_status: <BS.Badge bg="primary">Activating...</BS.Badge>,
            license_status: null,
            byniweeFormDisabled: true,
            activationBtnContents: (
                <BS.Button variant='success' className="mt-3 w-100" disabled type="submit" id="submit-byniwee-activation">
                    {niwhiboutik_po.status_messages.activating}
                    <BS.Spinner animation="border" size="sm" role="status" aria-hidden="true" className="ms-2" />
                </BS.Button>
            )
        });

        this.wp.get('/activate-license').then(status =>
        {
            let response = status.data;
            if (response.status && response.status === "success" && response.activated && response.activated === true)
            {
                this.setState({
                    byniwee_status: <BS.Badge bg="success">{response.message}</BS.Badge>,
                    license_status: (
                        <BS.Alert variant="success">{niwhiboutik_po.status_messages.activated}</BS.Alert>
                    ),
                    byniweeBtnContents: (
                        <BS.Button variant='primary' type="submit" id="submit-byniwee-credentials">
                            {niwhiboutik_po.status_messages.update}
                        </BS.Button>
                    ),
                    activationBtnContents: null,
                    byniweeFormDisabled: false,
                    license_activated: true,
                });

                window.location.reload();
            }
            else
            {
                this.setState({
                    byniwee_status: <BS.Badge bg="danger">{niwhiboutik_po.status_messages.error}</BS.Badge>,
                    license_status: (
                        <BS.Alert variant="danger">{niwhiboutik_po.status_messages.activation_error}: {response.data.message}</BS.Alert>
                    ),
                    byniweeFormDisabled: false,
                    activationBtnContents: (
                        <a className="btn btn-warning w-100 mt-3" href="https://plugins.byniwee.io/mon-compte/view-license-keys/" target="_blank" onClick={this.submitByniweeActivation}>
                            {niwhiboutik_po.status_messages.retry}
                        </a>
                    )
                });
                this.getLicenseInfo();

                console.error(status);
            }
        });
    }

    getLicenseInfo()
    {
        this.setState({
            license_info: (
                <h4 className="mb-3">
                    License info:
                    <span className="ms-2">
                        <BS.Spinner animation="border" size="sm" />
                    </span>
                </h4>
            )
        });

        this.wp.get('/activations-left').then(response =>
        {
            if (response.data)
            {
                let activations_left_message = niwhiboutik_po.status_messages.activations_left;

                if (response.data.activations_left === 1)
                {
                    activations_left_message = niwhiboutik_po.status_messages.activation_left;
                }

                this.setState({
                    license_info: (
                        <h4 className="mb-3">
                            License info:
                            <span className="ms-2">
                                <BS.Badge bg="info">{response.data.activations_left} {activations_left_message}</BS.Badge>
                            </span>
                        </h4>
                    )
                });
            }
            else
            {
                this.setState({
                    license_info: (
                        <h4 className="mb-3">
                            License info:
                            <span className="ms-2">
                                <BS.Badge bg="danger">{niwhiboutik_po.status_messages.error}</BS.Badge>
                            </span>
                        </h4>
                    )
                });
            }
        })
    }

    submitHiboutikParams(e)
    {
        e.preventDefault();

        const form = e.target;

        if (form.checkValidity() === false)
        {
            e.stopPropagation();
            this.setState({ validated: true });
            return;
        }
        else
        {
            this.setState({
                credentialsBtnContents: this.loadingBtn,
                hiboutikFormDisabled: true,
                hiboutikStatus: (
                    <BS.Badge bg="info">
                        {niwhiboutik_po.status_messages.loading}
                    </BS.Badge>
                ),
            });

            this.wp.post("/update-hiboutik-params", {
                hiboutik_link: this.state.hiboutik_link,
                hiboutik_login: this.state.hiboutik_login,
                hiboutik_key: this.state.hiboutik_key,
            }).then(response =>
            {
                this.testHiboutik();
                this.setState({
                    hiboutikFormDisabled: false,
                })
                if (response.status !== 200 && response.data.status !== "success")
                {
                    this.setState({
                        formStatus: response.data.message,
                        credentialsBtnContents: (
                            <BS.Button variant='danger' disabled type="submit" id="submit-hiboutik-credentials">
                                {niwhiboutik_po.status_messages.error}
                            </BS.Button>
                        )
                    });
                }
                else
                {
                    this.setState({
                        formStatus: <BS.Alert variant="success">{niwhiboutik_po.login_info_saved}.</BS.Alert>,
                        credentialsBtnContents: (
                            <BS.Button variant='success' disabled type="submit" id="submit-hiboutik-credentials">
                                {niwhiboutik_po.status_messages.success}
                            </BS.Button>
                        ),
                    });

                    setTimeout(() =>
                    {
                        this.setState({
                            formStatus: null,
                            credentialsBtnContents: (
                                <BS.Button variant='primary' type="submit" id="submit-hiboutik-credentials">
                                    {niwhiboutik_po.status_messages.update}
                                </BS.Button>
                            )
                        })
                    }, 3000);
                }

            }).catch(error =>
            {
                console.error(error);
                if (error.response.data.message != undefined)
                {
                    console.error(error.response.data.message);
                }
                else if (error.response.data != undefined)
                {
                    console.error(error.response.data);
                }

                if (error.response.status === 401)
                {
                    this.testHiboutik();
                }

                this.setState({
                    reqSuccess: false,
                    retry: true,
                    formStatus: <BS.Alert variant="danger">{niwhiboutik_po.error_saving_login_info}</BS.Alert>,
                    credentialsBtnContents: (
                        <BS.Button variant='warning' type="submit" id="submit-hiboutik-credentials">
                            {niwhiboutik_po.status_messages.retry}
                        </BS.Button>
                    )
                });
            });
        }
    }

    // Get Hiboutik Auth Info (if there is any)
    getHiboutikInfo()
    {
        return new Promise((resolve, reject) =>
        {
            this.wp.get('/get-hiboutik-params').then(response =>
            {
                this.setState({
                    hiboutik_link: response.data.data.hiboutik_url,
                    hiboutik_login: response.data.data.hiboutik_login,
                    hiboutik_key: response.data.data.hiboutik_key,
                });

                resolve(true);

            }).catch(error =>
            {
                console.error(error);
                reject(error);
            })
        });
    }

    getByniweeLicense()
    {
        return new Promise((resolve, reject) =>
        {
            this.wp.get('/get-byniwee-license').then(response =>
            {
                resolve(response.data);
            }).catch(error =>
            {
                reject(error);
            });
        });
    }

    // Test Hiboutik API
    testHiboutik()
    {
        return new Promise((resolve, reject) =>
        {
            this.setState({
                hiboutikStatus: <BS.Badge bg="primary">{niwhiboutik_po.status_messages.loading}...</BS.Badge>,
                hiboutikFormDisabled: true,
            });

            if (this.state.hiboutik_link !== "" && this.state.hiboutik_login !== "" && this.state.hiboutik_key !== "")
            {

                Axios.get(this.state.hiboutik_link + "/brands", {
                    auth: {
                        username: this.state.hiboutik_login,
                        password: this.state.hiboutik_key
                    }
                }).then(response =>
                {
                    if (response.status === 200)
                    {
                        this.setState({
                            hiboutikStatus: <BS.Badge bg="success">{niwhiboutik_po.auth_success}.</BS.Badge>,
                        });
                        resolve(true);
                    }
                }).catch(error =>
                {
                    console.error(error);
                    if (error.response.status === 401)
                    {
                        this.setState({
                            hiboutikStatus: <BS.Badge bg="danger">{niwhiboutik_po.auth_error}: {niwhiboutik_po.auth_error_messages.unauthorized}.</BS.Badge>,
                            credentialsBtnContents: (
                                <BS.Button variant='warning' type="submit" id="submit-hiboutik-credentials">
                                    {niwhiboutik_po.status_messages.retry}
                                </BS.Button>
                            )
                        });
                    }
                    else if (error.response.status === 500)
                    {
                        this.setState({
                            hiboutikStatus: <BS.Badge bg="danger">{niwhiboutik_po.auth_error}: {niwhiboutik_po.auth_error_messages.internal_error}.</BS.Badge>,
                            credentialsBtnContents: (
                                <BS.Button variant='danger' disabled type="submit" id="submit-hiboutik-credentials">
                                    {niwhiboutik_po.status_messages.error}
                                </BS.Button>
                            )
                        });
                    }
                    else if (error.response.status === 404)
                    {
                        this.setState({
                            hiboutikStatus: <BS.Badge bg="danger">{niwhiboutik_po.auth_error}: {niwhiboutik_po.auth_error_messages.page_not_found}.</BS.Badge>,
                            hiboutikFormDisabled: false,
                            credentialsBtnContents: (
                                <BS.Button variant='warning' type="submit" id="submit-hiboutik-credentials">
                                    {niwhiboutik_po.status_messages.retry}
                                </BS.Button>
                            )
                        });
                    }
                    else if (error.response.status === 0 && error.code === "ERR_NETWORK")
                    {
                        this.setState({
                            hiboutikStatus: <BS.Badge bg="danger">{niwhiboutik_po.auth_error}: {niwhiboutik_po.auth_error_messages.cannot_find_link}: {this.state.hiboutik_link}</BS.Badge>,
                            hiboutikFormDisabled: false,
                            credentialsBtnContents: (
                                <BS.Button variant='warning' type="submit" id="submit-hiboutik-credentials">
                                    {niwhiboutik_po.status_messages.retry}
                                </BS.Button>
                            )
                        });
                        setTimeout(() =>
                        {
                            Swal.fire({
                                title: niwhiboutik_po.auth_error,
                                text: niwhiboutik_po.auth_error_messages.cannot_find_link + ": " + this.state.hiboutik_link,
                                icon: 'error',
                                confirmButtonColor: '#3085d6',
                            })
                        }, 500);
                    }
                    else
                    {
                        this.setState({
                            hiboutikStatus: <BS.Badge bg="danger">{niwhiboutik_po.auth_error}: {niwhiboutik_po.status_messages.unknown_error}.</BS.Badge>,
                            credentialsBtnContents: (
                                <BS.Button variant='danger' disabled type="submit" id="submit-hiboutik-credentials">
                                    {niwhiboutik_po.status_messages.error}
                                </BS.Button>
                            )
                        });
                    }
                    resolve(false);
                });
            }
            else
            {
                this.setState({
                    hiboutikStatus: <BS.Badge bg="danger">{niwhiboutik_po.please.login_info}.</BS.Badge>,
                    hiboutikFormDisabled: false,
                    credentialsBtnContents: (
                        <BS.Button variant='primary' type="submit" id="submit-hiboutik-credentials">
                            {niwhiboutik_po.status_messages.save}
                        </BS.Button>
                    )
                });

                resolve(false);
            }
        })
    }

    handleInputChange(e)
    {
        const target = e.target;
        const value = target.value;
        const name = target.name;

        this.setState({
            [name]: value,
        });
    }

    submitByniweeKey(e)
    {
        e.preventDefault();

        const form = e.target;

        if (form.checkValidity() === false)
        {
            e.stopPropagation();
            this.setState({ validated: true });
            return;
        }
        else
        {
            this.setState({
                byniweeBtnContents: this.loadingBtn,
                byniweeFormDisabled: true,
                license_status: null,
                byniwee_status: (
                    <BS.Badge bg="primary">{niwhiboutik_po.status_messages.updating_license}...</BS.Badge>
                ),
                activationBtnContents: null
            });

            this.wp.post("/update-byniwee-license", {
                license: this.state.license,
            }).then(response =>
            {
                this.setState({
                    byniweeFormDisabled: false,
                    byniwee_status: (
                        <BS.Badge bg="primary">{niwhiboutik_po.status_messages.checking_license_validity}...</BS.Badge>
                    ),
                });

                this.getByniweeLicense().then(response =>
                {
                    this.setLicenseStatus(response);
                });

            }).catch(error =>
            {
                console.error(error);

                if (error.response.status)
                {
                    if (error.response.status === 404)
                    {
                        this.setState({
                            license_status: <BS.Alert variant="danger">{niwhiboutik_po.status_messages.unknown_error}</BS.Alert>,
                            byniweeBtnContents: (
                                <BS.Button variant="danger" type="submit">
                                    {niwhiboutik_po.status_messages.error}
                                </BS.Button>
                            )
                        });
                    }
                    else if (error.response.status === 500)
                    {
                        this.setState({
                            license_status: <BS.Alert variant="danger">{niwhiboutik_po.status_messages.unknown_error}</BS.Alert>,
                            byniweeBtnContents: (
                                <BS.Button variant="danger" type="submit">
                                    {niwhiboutik_po.status_messages.error}
                                </BS.Button>
                            )
                        });
                    }
                    else
                    {
                        this.setState({
                            license_status: <BS.Alert variant="danger">{niwhiboutik_po.status_messages.unknown_error}</BS.Alert>,
                            byniweeBtnContents: (
                                <BS.Button variant="danger" type="submit">
                                    {niwhiboutik_po.status_messages.error}
                                </BS.Button>
                            )
                        });
                    }
                }
            });
        }
    }

    render()
    {
        return (
            <div className="container">
                <div>
                    <h1>{niwhiboutik_po.settings.title}</h1>
                </div>
                {
                    this.state.is_activated
                        ?
                        <Block>
                            {this.state.importAlert}
                            <BS.Row>
                                <BS.Col>
                                    <h2>{niwhiboutik_po.hiboutik_login_data}</h2>
                                    <h4 className="mb-3">
                                        {niwhiboutik_po.hiboutik_connection_status}:
                                        <span className="ms-2">{this.state.hiboutikStatus}</span>
                                    </h4>
                                    <BS.Form noValidate validated={this.state.validated} autoComplete="off" onSubmit={this.submitHiboutikParams}>
                                        {this.state.formStatus}
                                        <fieldset disabled={this.state.hiboutikFormDisabled}>
                                            <BS.Form.Group className="mb-3">
                                                <BS.Form.Label>{niwhiboutik_po.settings.hiboutik_link}</BS.Form.Label>
                                                <BS.Form.Control
                                                    name="hiboutik_link"
                                                    value={this.state.hiboutik_link}
                                                    required
                                                    onChange={this.handleInputChange}
                                                    autoComplete="off"
                                                    data-lpignore="true"
                                                    data-form-type="other"
                                                    type="url"
                                                    placeholder="https://masociete.hiboutik.com/api"
                                                />
                                                <BS.Form.Text className="text-muted">
                                                    {niwhiboutik_po.settings.hiboutik_link_help}
                                                </BS.Form.Text>
                                            </BS.Form.Group>
                                            <BS.Form.Group className="mb-3">
                                                <BS.Form.Label>{niwhiboutik_po.settings.hiboutik_login}</BS.Form.Label>
                                                <BS.Form.Control
                                                    name="hiboutik_login"
                                                    value={this.state.hiboutik_login}
                                                    required
                                                    onChange={this.handleInputChange}
                                                    autoComplete="off"
                                                    data-lpignore="true"
                                                    data-form-type="other"
                                                    type="email"
                                                    placeholder="admin@exemple.fr"
                                                />
                                                <BS.Form.Text className="text-muted">
                                                    {niwhiboutik_po.settings.hiboutik_login_help}
                                                </BS.Form.Text>
                                            </BS.Form.Group>
                                            <BS.Form.Group className="mb-3">
                                                <BS.Form.Label>{niwhiboutik_po.settings.hiboutik_token}</BS.Form.Label>
                                                <BS.Form.Control
                                                    name="hiboutik_key"
                                                    value={this.state.hiboutik_key}
                                                    required
                                                    onChange={this.handleInputChange}
                                                    autoComplete="off"
                                                    data-lpignore="true"
                                                    data-form-type="other"
                                                    type="password"
                                                    placeholder="123456789azerty"
                                                />
                                                <BS.Form.Text className="text-muted">
                                                    {niwhiboutik_po.settings.hiboutik_token_help} (<a href="https://faq.hiboutik.com/fr/api-developpement/api-getting-started-authorization-api-credentials" target="_blank" title={niwhiboutik_po.settings.hiboutik_doc}>{niwhiboutik_po.settings.hiboutik_doc}</a>)
                                                </BS.Form.Text>
                                            </BS.Form.Group>
                                        </fieldset>
                                        {this.state.credentialsBtnContents}
                                    </BS.Form>
                                </BS.Col>
                            </BS.Row>
                        </Block>
                        : null}
                <Block>

                    <BS.Row>
                        <BS.Col>
                            <h2>{niwhiboutik_po.settings.plugin_activation}</h2>
                            <h4 className="mb-3">
                                Activation status:
                                <span className="ms-2">{this.state.byniwee_status}</span>
                            </h4>
                            {this.state.license_info}
                            <fieldset disabled={this.state.activationFormDisabled}>
                                <BS.Form noValidate validated={this.state.validated} autoComplete="off" onSubmit={this.submitByniweeKey}>
                                    {this.state.license_status}
                                    <fieldset disabled={this.state.byniweeFormDisabled}>
                                        <BS.Form.Group className="mb-3">
                                            <BS.Form.Label>{niwhiboutik_po.settings.byniwee_key}</BS.Form.Label>
                                            <BS.Form.Control
                                                name="license"
                                                value={this.state.license}
                                                required
                                                onChange={this.handleInputChange}
                                                autoComplete="off"
                                                data-lpignore="true"
                                                data-form-type="other"
                                                type={this.state.license_field_type}
                                                placeholder="niwhiboutik-1234-ABCD-5678-EFGH-9123-IJKL-MNOP-QRST-UVWX-YZ12-3456-7890"
                                            />
                                            <BS.Form.Text className="text-muted">
                                                {niwhiboutik_po.settings.byniwee_key_help} (<a target="_blank" title={niwhiboutik_po.settings.byniwee_doc} href="https://plugins.byniwee.io/faq">{niwhiboutik_po.settings.byniwee_doc}</a>)
                                            </BS.Form.Text>
                                        </BS.Form.Group>
                                    </fieldset>
                                    <div className="d-flex w-100 align-items-center justify-content-between">
                                        {this.state.byniweeBtnContents}
                                    </div>
                                </BS.Form>
                            </fieldset>
                            {this.state.activationBtnContents}
                        </BS.Col>
                    </BS.Row>
                </Block>
            </div>
        );
    }
}

export default Settings;