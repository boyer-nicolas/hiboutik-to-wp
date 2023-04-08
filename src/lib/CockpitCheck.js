import Axios from "axios";
import Cookies from "js-cookie";

class CockpitCheck {
    constructor() {
        this.wp = Axios.create({
            baseURL: "/wp-json/niwhiboutik/v1",
            headers: {
                "Authorization": "Bearer " + Cookies.get("nwh_api_token")
            }
        });

        this.testWp();
    }

    get_wp_api() {
        return this.wp;
    }

    // Test WP Api
    testWp() {
        this.wp.get("/")
            .then(response => {
                if (response.status && response.status !== 200) {
                    console.error(response);
                    alert("Impossible de se connecter à votre site Wordpress. Si un plugin de cache est activé, essayez de purger le cache ou de désactiver et réactiver le plugin.");
                }
            })
            .catch(error => {
                if (error.response.status === 401) {
                    alert("Impossible de se connecter à votre site Wordpress. Si un plugin de cache est activé, essayez de purger le cache ou de désactiver et réactiver le plugin.");
                } else {
                    console.error(error);
                    alert("Impossible de se connecter à votre site Wordpress. Si un plugin de cache est activé, essayez de purger le cache ou de désactiver et réactiver le plugin.");
                }
            });
    }
}

export default CockpitCheck;