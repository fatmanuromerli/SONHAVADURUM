<template>
  <div>
    <div class="wrapper" :class="wrapperClass">
      <header>
        <font-awesome-icon :icon="['fas', 'arrow-left']" id="geri" class="bx" />
        Hava Durumu
      </header>

      <section class="input-part">
        <p class="info-txt"></p>
        <input
          v-model="city"
          @keyup.enter="onEnter"
          type="text"
          placeholder="Şehir"
          spellcheck="false"
          required
        />
        <div>
          <!-- Şehirleri dinamik olarak listele -->
          <ul id="onerilensehirler" v-if="citySuggestions.length">
            <li
              v-for="(sehir, index) in citySuggestions"
              :key="index"
              @click="selectCity(sehir)"
            >
              {{ sehir.name }}
            </li>
          </ul>
        </div>
        <button @click="showFavorites" class="fav btn btn-primary">
          Favorilediğin şehirleri görüntüle
        </button>
        <ul id="onerilensehirler" v-if="showFavoriteList">
          <li
            v-for="(sehir, index) in favorites"
            :key="index"
            @click="selectCity2(sehir)"
          >
            {{ sehir.name }}
          </li>
        </ul>
        <hr />
      </section>

      <section v-if="weatherData" class="weather-part">
        <section v-if="weatherData" class="weather-part">
          <img :src="weatherData.wIcon" alt="Weather Icon" />
          <div class="temp">
            <!-- Diğer içerikler -->
          </div>
        </section>
        <div class="temp">
          <span class="numb">{{ weatherData.main.temp }}</span>
          <span class="deg">°</span>C
        </div>
        <div class="weather">{{ weatherData.weather[0].description }}</div>
        <div class="location">
          <font-awesome-icon :icon="['fas', 'map']" class="bx" />
          <span>{{ weatherData.name }}</span>
        </div>
        <div class="bottom-details">
          <div class="column feels">
            <font-awesome-icon :icon="['fas', 'thermometer-half']" class="bx" />
            <div class="details">
              <div class="temp">
                <span class="numb-2">{{ weatherData.main.feels_like }}</span>
                <span class="deg">°</span>C
              </div>
              <p>Feels like</p>
            </div>
          </div>
          <div class="column humidity">
            <font-awesome-icon :icon="['fas', 'droplet']" class="bx" />
            <div class="details">
              <span>{{ weatherData.main.humidity }}</span>
              <p>Humidity</p>
            </div>
          </div>
        </div>
        <button @click="addToFavorites(city)" class="btn btn-primary">
          Favorilere Ekle
        </button>
        <br />
        <button @click="showFavorites" class="fav btn btn-primary">
          Favorilediğin şehirleri görüntüle
        </button>
        <ul id="onerilensehirler" v-if="showFavoriteList">
          <li
            v-for="(sehir, index) in favorites"
            :key="index"
            @click="selectCity2(sehir)"
          >
            {{ sehir.name }}
          </li>
        </ul>
      </section>

      <p v-if="errorMessage" class="info-txt error">{{ errorMessage }}</p>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import { library } from "@fortawesome/fontawesome-svg-core";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
  faArrowLeft,
  faMap,
  faThermometerHalf,
  faDroplet,
} from "@fortawesome/free-solid-svg-icons";
library.add(faArrowLeft, faMap, faThermometerHalf, faDroplet);

export default {
  name: "WeatherComponent",
  components: {
    FontAwesomeIcon,
  },
  data() {
    return {
      apiKey: "e015f644263d7dfc6fd26d20eb9d287c",
      city: "",
      favori: "",
      weatherData: null,
      favorimi: "",
      errorMessage: "",
      inputField: "",
      cities: [],
      secilenSehir: "",
      infoTxt: "",
      isError: false,
      favorites: [],
      isLoading: false,
      infoClass: "",
      wrapperClass: "",
      showFavoriteList: false,
      citySuggestions: [],
    };
  },
  methods: {
    async onEnter() {
      if (this.city) {
        await this.checkCityInDB();
      }
    },
    async checkCityInDB() {
      if (!this.city) {
        this.citySuggestions = [];
        return;
      }

      try {
        const form_data = new FormData();
        form_data.append("city", this.city);
        form_data.append("operation", "sehir");

        const response = await axios.post(
          "http://localhost/havadurum/havadurum.php",
          form_data,
          { headers: { "Content-Type": "multipart/form-data" } }
        );

        if (response.data.status === "200") {
          this.citySuggestions = response.data.data;
        } else {
          this.citySuggestions = [];
        }
      } catch (error) {
        console.error("Veritabanı hatası:", error);
        this.errorMessage = "Şehir önerileri alınırken bir hata oluştu.";
      }
    },

    async selectCity(sehir) {
      if (!sehir || !sehir.name) {
        this.errorMessage = "Geçersiz şehir seçildi.";
        return;
      }

      this.city = sehir.name;
      this.citySuggestions = [];

      await this.requestApi(sehir.name);
    },

    weatherDetails(info) {
      if (info.cod == "404") {
        this.errorMessage = `${this.city} şehri bulunamadı...`;
        this.weatherData = null;
      } else {
        const city = info.name || "Bilinmiyor";
        const country = info.sys?.country || "Bilinmiyor";
        const weather = info.weather ? info.weather[0] : null;
        const description = weather
          ? weather.description
          : "Tanımlanamayan hava durumu";
        const id = weather ? weather.id : null;
        const { feels_like, humidity, temp } = info.main || {};

        if (
          !info.main ||
          temp === undefined ||
          feels_like === undefined ||
          humidity === undefined
        ) {
          this.errorMessage = "Geçersiz hava durumu verisi.";
          this.weatherData = null;
          return;
        }

        let wIcon = "";
        if (id === 800) {
          wIcon = "/icons/clear.svg";
        } else if (id >= 200 && id <= 232) {
          wIcon = "/icons/storm.svg";
        } else if (id >= 600 && id <= 622) {
          wIcon = "/icons/snow.svg";
        } else if (id >= 701 && id <= 781) {
          wIcon = "/icons/haze.svg";
        } else if (id >= 801 && id <= 804) {
          wIcon = "/icons/clouds.svg";
        } else if ((id >= 300 && id <= 321) || (id >= 500 && id <= 531)) {
          wIcon = "/icons/rain.svg";
        }

        this.weatherData = {
          name: city,
          wIcon: wIcon,
          main: {
            temp: Math.floor(temp - 273.15),
            feels_like: Math.floor(feels_like - 273.15),
            humidity,
          },
          weather: description,
          location: `${city}, ${country}`,
        };

        this.errorMessage = "";
        this.infoClass = "active";
        this.wrapperClass = "active";
      }
    },
    removeActiveClass() {
      this.wrapperClass = "";
    },

    async requestApi(city) {
      if (!city) {
        this.errorMessage =
          "Şehir adı boş. Lütfen geçerli bir şehir adı girin.";
        return;
      }

      const api = `https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${this.apiKey}`;

      try {
        const response = await axios.get(api);
        this.weatherDetails(response.data);
      } catch (error) {
        this.weatherData = null;
        this.errorMessage =
          "API hatası: " +
          (error.response ? error.response.data.message : error.message);
      }
    },
    async addToFavorites(city) {
      var form_data = new FormData();
      form_data.append("city", city);
      form_data.append("operation", "favori");
      try {
        const response = await axios.post(
          "http://localhost/fatodo/fatodo.php",
          form_data,
          {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          }
        );

        const dbResponses = response.data.status;

        if (dbResponses === "202") {
          this.checkIfFavorite(city);
        } else if (dbResponses === "200") {
          alert(`${city} şehri favorilere eklendi!`);
        } else {
          alert(`Hata: ${response.data.message}`);
        }
      } catch (error) {
        console.error("Favori eklerken hata oluştu:", error);
      }
    },
    async checkIfFavorite(city) {
      var form_data = new FormData();
      form_data.append("city", city);
      form_data.append("operation", "favori");

      try {
        const response = await axios.post(
          "http://localhost/havadurum/havadurum.php",
          form_data,
          {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          }
        );

        const result = response.data;

        if (result.status === "202") {
          alert(`${city} zaten favori listenizde!`);
        }

        return true;
      } catch (error) {
        console.error("Favoriler kontrol edilirken hata oluştu:", error);
        return false;
      }
    },

    async showFavorites() {
      this.showFavoriteList = !this.showFavoriteList;

      if (this.showFavoriteList) {
        let form_data = new FormData();
        form_data.append("city", this.city);
        form_data.append("operation", "favorigetir");

        try {
          const response = await axios.post(
            "http://localhost/havadurum/havadurum.php",
            form_data,
            {
              headers: {
                "Content-Type": "multipart/form-data",
              },
            }
          );

          const result = response.data;
          if (result.status === "200") {
            this.favorites = result.data;
          } else if (result.status === "no_favorites") {
            alert("Favori listeniz boş.");
            this.showFavoriteList = false;
          } else {
            alert("Favori şehirler getirilemedi.");
            this.showFavoriteList = false;
          }
        } catch (error) {
          console.error("Favori şehirleri alırken hata oluştu:", error);
        }
      }
    },
    async selectCity2(sehir) {
      this.city = sehir.name.trim().toLowerCase();

      const isAlreadyFavorite = this.favorites.some(
        (favorite) => favorite.name.trim().toLowerCase() === this.city
      );

      if (isAlreadyFavorite) {
        alert(
          `${sehir.name} zaten favori listenizde! Hava durumu detayları yükleniyor.`
        );
        this.requestApi(sehir.name);
      } else {
        await this.addToFavorites(sehir.name);
        this.requestApi(sehir.name);
      }
    },
  },
};
</script>

<style>
/* @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"); */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}

body {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: #43affc !important ;
}

.wrapper {
  width: 350px;
  border-radius: 7px;
  background: #fff;
}

.wrapper header {
  color: #43affc;
  font-size: 18px;
  font-weight: 500;
  padding: 16px 15px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid #bfbfbf;
}
a {
  color: #43affc !important;
  font-size: 18px;
  font-weight: 500;
  padding: 16px 15px;
  display: none;
  align-items: center;
  text-decoration: none;
  /* border-bottom: 1px solid #bfbfbf */
}
a i {
  color: #ffbb00;
}
a.active {
  color: #43affc !important;
  font-size: 18px;
  font-weight: 500;
  padding: 16px 15px;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  text-decoration: none;
  /* border-bottom: 1px solid #bfbfbf */
}

.favdiv {
  display: flex;
  width: 100%; /* div tam genişlikte olsun */
}

header i {
  cursor: pointer;
  font-size: 0px;
  margin-right: 6px;
}
.wrapper.active header i {
  font-size: 30px;
  margin-left: 5px;
}
.wrapper .input-part {
  margin: 20px 25px 30px;
}
.wrapper.active .input-part {
  display: none;
}
.input-part .info-txt {
  display: none;
  font-size: 15px;
  text-align: center;
  padding: 12px 10px;
  border-radius: 7px;
  margin-bottom: 10px;
}

.info-txt.error {
  display: block;
  color: #721c24;
  background: #f8d7da;
  border: 1px solid #f5c6cb;
}

.info-txt.pending {
  display: block;
  color: #0c5460;
  background: #d1ecf1;
  border: 1px solid #bee5eb;
}

.input-part :where(input, button) {
  width: 100%;
  height: 55px;
  border: none;
  outline: none;
  border-radius: 7px;
}

.input-part input {
  text-align: center;
  border: 1px solid #bfbfbf;
}

.input-part input:is(:focus, :valid) {
  border: 2px solid #43affc;
}

.input-part .seperator {
  height: 1px;
  width: 100%;
  margin: 25px 0;
  background-color: #ccc;
  display: flex;
  align-items: center;
  justify-content: center;
}

.seperator::before {
  content: "veya";
  background: #fff;
  color: #ccc;
  padding: 0 15px;
  font-size: 15px;
}

.input-part button {
  color: #fff;
  background-color: #43affc;
  cursor: pointer;
}

/* ---- Weather Part */
.wrapper .weather-part {
  margin: 30px 0 0;
  display: none;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}
.wrapper.active .weather-part {
  display: flex;
}
.weather-part img {
  max-width: 125px;
}
.weather-part .temp {
  display: flex;
  font-size: 72px;
  font-weight: 500;
}
.weather-part .temp .numb {
  font-weight: 600;
}
.weather-part .temp .deg {
  font-size: 40px;
  margin: 10px 5px 0 0;
  display: block;
}
.weather-part .location {
  display: flex;
  align-items: center;
  font-size: 21px;
  margin-bottom: 30px;
}
.location i {
  font-size: 22px;
  margin-right: 5px;
}
.weather-part .bottom-details {
  width: 100%;
  display: flex;
  align-items: center;
  border-top: 1px solid #bfbfbf;
  justify-content: space-between;
}
.bottom-details .column {
  width: 100%;
  display: flex;
  padding: 15px 0;
  align-items: center;
  justify-content: center;
}
.column i {
  color: #43affc;
  font-size: 40px;
}
.column.humidity {
  border-left: 1px solid #bfbfbf;
}
.details .temp,
.humidity span {
  font-size: 18px;
  font-weight: 500;
  margin-top: -4px;
}
.details .temp .deg {
  margin: 0;
  font-size: 17px;
  padding: 0 2px 0 1px;
}
.details p {
  font-size: 14px;
  margin-top: -6px;
}
ul li {
  list-style-type: none;
}
.sehirsecenek {
  display: inline-block;
  padding: 2px 2px;
  margin: 2px;
  background-color: white;
  color: black;
  border-radius: 2px;
  cursor: pointer;
  text-align: center;
  transition: background-color 0.3s;
  display: block;
}

.sehirsecenek:hover {
  background-color: #43affc;
  color: white;
}

.fav {
  display: none;
  justify-content: center;
  align-items: center;
}

.fav-active {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
