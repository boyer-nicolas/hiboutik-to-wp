import React from "react";
import * as BS from "react-bootstrap";
import Axios from "axios";
import CockpitCheck from '../lib/CockpitCheck';

class CheckHiboutik extends React.Component
{
    constructor(props)
    {
        super(props);
        this.state = {
            niweeApiToken: '',
            validated: false,
            reqSent: false,
            reqSuccess: false,
            retry: false,
            saveBtnDisabled: false,
            isSubmitting: false,
            hiboutikStatus: "",
            hiboutik_link: "",
            hiboutik_login: "",
            hiboutik_key: "",
        };

        const checks = new CockpitCheck();
        this.wp = checks.get_wp_api();

        this.getHiboutikInfo().then(status =>
        {
            if (status === true)
            {
                setTimeout(() =>
                {
                    this.testHiboutik();
                }, 1000);
            }
            else
            {
                this.setState({
                    hiboutikStatus: <BS.Badge bg="danger">Erreur lors de la récupération des informations.</BS.Badge>
                });
            }
        });
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
                    saveBtnDisabled: false
                });

                resolve(true);

            }).catch(error =>
            {
                console.error(error);
                reject(error);
            })
        });
    }

    // Test Hiboutik API
    testHiboutik()
    {
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
                    return true;
                }
            }).catch(error =>
            {
                console.error(error);
                if (error.response.status === 401)
                {
                    this.setState({
                        hiboutikStatus: <BS.Badge bg="danger">Authentification à Hiboutik erronnée: Non autorisé. Veuillez vérifier vos informations de connection dans l'onglet des paramètres.</BS.Badge>
                    });
                    return false;
                }
                else if (error.response.status === 500)
                {
                    this.setState({
                        hiboutikStatus: <BS.Badge bg="danger">Authentification à Hiboutik erronnée: Erreur interne.</BS.Badge>
                    });
                    return false;
                }
                else if (error.response.status === 404)
                {
                    this.setState({
                        hiboutikStatus: <BS.Badge bg="danger">Authentification à Hiboutik erronnée: Page non trouvée. Veuillez vérifier vos informations de connection dans l'onglet des paramètres.</BS.Badge>
                    });
                    return false;
                }
                else if (error.response.status === 0 && error.code === "ERR_NETWORK")
                {
                    this.setState({
                        hiboutikStatus: <BS.Badge bg="danger">Authentification à Hiboutik erronnée: Impossible d'appeler le lien: {this.state.hiboutik_link}. Veuillez vérifier vos informations de connection dans l'onglet des paramètres.</BS.Badge>
                    });
                    setTimeout(() =>
                    {
                        alert('Vérifiez que le lien que vous avez renseigné pour Hiboutik correspond au schema suivant: https://votrecompte.hiboutik.com/api');
                    }, 500);
                    return false;
                }
                else
                {
                    this.setState({
                        hiboutikStatus: <BS.Badge bg="danger">Authentification à Hiboutik erronnée: Erreur inconnue. Veuillez vérifier vos informations de connection dans l'onglet des paramètres.</BS.Badge>
                    });
                    return false;
                }
            });
        }
        else
        {
            this.setState({
                hiboutikStatus: <BS.Badge bg="danger">Authentification à Hiboutik erronnée: Veuillez renseigner vos informations de connexion dans l'onglet des paramètres.</BS.Badge>
            });
            return false;
        }
    }

    render()
    {
        return (
            <div>
                {this.state.hiboutikStatus}
            </div>
        );
    }
}

export default CheckHiboutik;