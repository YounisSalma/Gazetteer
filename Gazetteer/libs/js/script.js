var map;

var streets = L.tileLayer(
  "https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}",
  {
    attribution:
      "Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012",
  }
);

var satellite = L.tileLayer(
  "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
  {
    attribution:
      "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community",
  }
);

var basemaps = {
  Streets: streets,
  Satellite: satellite,
};

var geoButton = L.easyButton("fa-globe-americas fa-xl", function (btn, map) {
  $("#geoLocationModal").modal("show");
});
var currencyButton = L.easyButton(
  "fa-money-check-alt fa-xl",
  function (btn, map) {
    $("#currencyModal").modal("show");
  }
);
var generalInfoButton = L.easyButton(
  "fas fa-portrait fa-xl",
  function (btn, map) {
    $("#generalInfoModal").modal("show");
  }
);
var otherButton = L.easyButton("fas fa-book fa-xl", function (btn, map) {
  $("#otherModal").modal("show");
});
var newsButton = L.easyButton("fas fa-comment-dots fa-xl", function (btn, map) {
  $("#newsModal").modal("show");
});
var WeatherButton = L.easyButton(
  "fas fa-cloud-sun-rain fa-xl",
  function (btn, map) {
    $("#WeatherModal").modal("show");
  }
);

const airportIcon = L.icon({
  iconUrl: "libs/images/airport-marker-icon.png",
  iconSize: [32, 32],
  iconAnchor: [16, 16],
});

document.addEventListener("DOMContentLoaded", function () {
  const preloader = document.getElementById("preloader");
  const countrySelect = document.getElementById("countrySelect");
  const currencySelect = document.getElementById("currencySelect");
  const amountInput = document.getElementById("amount");
  const convertedAmount = document.getElementById("convertedAmount");

  function hidePreloader() {
    preloader.style.display = "none";
  }

  function handleImageError(event) {
    const img = event.target;
    img.src = "libs/images/default-image.png";
  }

  function updateConvertedAmount() {
    const amount = parseFloat(amountInput.value) || 0;
    const selectedOption = currencySelect.options[currencySelect.selectedIndex];
    const rate = parseFloat(selectedOption.getAttribute("data-rate")) || 0;
    const convertedValue = (amount * rate).toFixed(2);
    convertedAmount.value = convertedValue;
  }

  currencySelect.addEventListener("change", updateConvertedAmount);
  amountInput.addEventListener("input", updateConvertedAmount);

  document
    .getElementById("firstNewsImg")
    .addEventListener("error", handleImageError);
  document
    .getElementById("secondNewsImg")
    .addEventListener("error", handleImageError);
  document
    .getElementById("country-thumbnail")
    .addEventListener("error", handleImageError);
  document
    .getElementById("country-flag")
    .addEventListener("error", handleImageError);
  document
    .getElementById("country-flag-weather")
    .addEventListener("error", handleImageError);

  let userLocationData = null;

  fetch("libs/php/geoData.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "countryBorders=true",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.countryBorders) {
        const sortedCountries = data.countryBorders.features
          .map((feature) => feature.properties.name)
          .sort();

        sortedCountries.forEach((name) => {
          const option = document.createElement("option");
          option.value = name;
          option.textContent = name;
          document.getElementById("countrySelect").appendChild(option);
        });

        map = L.map("map", {
          layers: [streets],
        }).setView([0, 0], 2);

        let layerControl = L.control.layers(basemaps).addTo(map);
        let buttonsAdded = false;

        window.focusCountry = function (name) {
          const countryFeature = data.countryBorders.features.find(
            (feature) => feature.properties.name === name
          );

          if (countryFeature) {
            const bounds = L.geoJSON(countryFeature, {
              style: {
                color: "#3388ff",
                weight: 2,
                opacity: 1,
                fillOpacity: 0.2,
                lineCap: "round",
                lineJoin: "round",
              },
            }).getBounds();
            hidePreloader();
            map.fitBounds(bounds);
          }
        };

        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            function (position) {
              const userLat = position.coords.latitude;
              const userLng = position.coords.longitude;

              fetch("libs/php/geoData.php", {
                method: "POST",
                headers: {
                  "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `lat=${userLat}&lng=${userLng}`,
              })
                .then((response) => response.json())
                .then((locationData) => {
                  if (locationData.countryName) {
                    const countryName = locationData.countryName;
                    // console.log("User country:", countryName);

                    const countrySelect =
                      document.getElementById("countrySelect");
                    countrySelect.value = countryName;
                    countrySelect.dispatchEvent(new Event("change"));
                  } else {
                    console.error("Unable to determine user's country.");
                  }
                })
                .catch((error) => {
                  console.error("Error fetching user location data:", error);
                });
            },
            function (error) {
              console.error("Error getting user location:", error);
            }
          );
        } else {
          console.error("Geolocation is not supported by this browser.");
        }

        function updateElementContent(id, content) {
          const element = document.getElementById(id);
          if (content) {
            element.textContent = content;
            element.style.display = "";
          } else {
            element.style.display = "none";
          }
        }

        const handleCountryChange = async function (selectedCountry) {
          if (!buttonsAdded) {
            otherButton.addTo(map);
            geoButton.addTo(map);
            generalInfoButton.addTo(map);
            currencyButton.addTo(map);
            newsButton.addTo(map);
            WeatherButton.addTo(map);
            buttonsAdded = true;
          }

          let countryData;
          if (
            userLocationData &&
            userLocationData.countryName === selectedCountry
          ) {
            countryData = userLocationData;
          } else {
            try {
              const response = await fetch("libs/php/geoData.php", {
                method: "POST",
                headers: {
                  "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `countryName=${encodeURIComponent(selectedCountry)}`,
              });

              if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Network response was not ok: ${errorText}`);
              }

              const responseBody = await response.text();
              try {
                countryData = JSON.parse(responseBody);
              } catch (e) {
                throw new Error(`Invalid JSON response: ${responseBody}`);
              }
            } catch (error) {
              console.error("Error fetching country data:", error);
              return;
            }
          }

          const requiredFields = [
            "latitude",
            "longitude",
            "currency",
            "currentCurrencyCode",
            "roadSide",
            "speedType",
            "continent",
            "population",
            "landArea",
            "countrySummary",
            "countryWikiURL",
            "countryExchangeRate",
            "borders",
            "sunrise",
            "sunset",
            "currentTime",
            "capitalCity",
            "countryCode",
            "countryThumbnailImg",
            "citySummary",
            "cityThumbnailImg",
            "cityWikiURL",
            "cityName",
            "firstNewsTitle",
            "firstNewsURL",
            "firstNewsDescription",
            "firstNewsPublishedDate",
            "firstNewsImgUrl",
            "firstNewsName",
            "secondNewsTitle",
            "secondNewsURL",
            "secondNewsDescription",
            "secondNewsPublishedDate",
            "secondNewsImgUrl",
            "secondNewsName",
            "iataAirports",
            "exchangeRateList",
            "weatherTempOne",
            "weatherTempTwo",
            "weatherTempThree",
            "weatherTempFour",
            "weatherTempFive",
            "weatherDescription",
            "weatherDateTwo",
            "weatherDateThree",
            "weatherDateFour",
            "weatherDateFive",
          ];

          const hasAllRequiredFields = requiredFields.every(
            (field) => countryData[field] !== undefined
          );

          if (hasAllRequiredFields) {
            document.getElementById("selectedCountry").textContent =
              selectedCountry;
            document.getElementById("selectedCountryWeather").textContent =
              selectedCountry;
            document.getElementById("selectedCountryNews").textContent =
              selectedCountry;
            document.getElementById("capitalCity").textContent =
              countryData.capitalCity;
            document.getElementById("countrySize").textContent =
              numeral(countryData.landArea).format(0, 0) + " km²";
            document.getElementById("countryContinent").textContent =
              countryData.continent;
            document.getElementById("lat&lng").textContent =
              countryData.latitude + ", " + countryData.longitude;
            document.getElementById("countryBorders").textContent =
              countryData.borders;
            document.getElementById("countryPopulation").textContent = numeral(
              countryData.population
            ).format(0, 0);
            document.getElementById("countryRoadSide").textContent =
              countryData.roadSide;
            document.getElementById("countrySpeedMeasurement").textContent =
              countryData.speedType;
            document.getElementById("currentDate").textContent =
              new Date().toLocaleDateString("en-GB");
            document.getElementById("countryCurrentTime").textContent =
              countryData.currentTime;
            document.getElementById("countrySunRise").textContent =
              countryData.sunrise;
            document.getElementById("countrySunSet").textContent =
              countryData.sunset;
            document.getElementById("selectedCountryInfo").textContent =
              selectedCountry;
            document.getElementById("countryWikiLink").href =
              "https://" + countryData.countryWikiURL;
            document.getElementById("countryWikiLink").textContent =
              "Read more..";
            document.getElementById("selectedCity").textContent =
              countryData.cityName;
            document.getElementById("cityWikiLink").href =
              "https://" + countryData.cityWikiURL;
            document.getElementById("cityWikiLink").textContent = "Read more..";
            document.getElementById("firstNewsLink").href =
              countryData.firstNewsURL;
            document.getElementById("secondNewsLink").href =
              countryData.secondNewsURL;
            document.getElementById("weatherTempOne").textContent =
              countryData.weatherTempOne;
            document.getElementById("weatherTempTwo").textContent =
              countryData.weatherTempTwo;
            document.getElementById("weatherTempThree").textContent =
              countryData.weatherTempThree;
            document.getElementById("weatherTempFour").textContent =
              countryData.weatherTempFour;
            document.getElementById("weatherTempFive").textContent =
              countryData.weatherTempFive;
            document.getElementById("weatherDateTwo").textContent =
              countryData.weatherDateTwo;
            document.getElementById("weatherDateThree").textContent =
              countryData.weatherDateThree;
            document.getElementById("weatherDateFour").textContent =
              countryData.weatherDateFour;
            document.getElementById("weatherDateFive").textContent =
              countryData.weatherDateFive;
            document.getElementById("weatherDescription").innerHTML =
              "You should expect <b>" + countryData.weatherDescription + "</b>";
            updateElementContent("countrySummary", countryData.countrySummary);
            updateElementContent("citySummary", countryData.citySummary);
            updateElementContent("firstNewsTitle", countryData.firstNewsTitle);
            updateElementContent(
              "secondNewsTitle",
              countryData.secondNewsTitle
            );
            updateElementContent("firstNewsName", countryData.firstNewsName);
            updateElementContent("secondNewsName", countryData.secondNewsName);

            document
              .getElementById("countryWikiLink")
              .addEventListener("click", function (e) {
                e.preventDefault();
                window.open(
                  document.getElementById("countryWikiLink").href,
                  "_blank"
                );
              });

            document
              .getElementById("cityWikiLink")
              .addEventListener("click", function (e) {
                e.preventDefault();
                window.open(
                  document.getElementById("cityWikiLink").href,
                  "_blank"
                );
              });

            document
              .getElementById("firstNewsLink")
              .addEventListener("click", function (e) {
                e.preventDefault();
                window.open(
                  document.getElementById("firstNewsLink").href,
                  "_blank"
                );
              });

            document
              .getElementById("secondNewsLink")
              .addEventListener("click", function (e) {
                e.preventDefault();
                window.open(
                  document.getElementById("secondNewsLink").href,
                  "_blank"
                );
              });

            document.getElementById(
              "country-flag"
            ).src = `https://countryflagsapi.netlify.app/flag/${countryData.countryCode}.svg`;

            document.getElementById(
              "country-flag-weather"
            ).src = `https://countryflagsapi.netlify.app/flag/${countryData.countryCode}.svg`;

            document.getElementById("country-thumbnail").src =
              countryData.countryThumbnailImg;

            document.getElementById("firstNewsImg").src =
              countryData.firstNewsImgUrl;

            document.getElementById("secondNewsImg").src =
              countryData.secondNewsImgUrl;

            currencySelect.innerHTML =
              '<option value="">--Currency Code--</option>';
            countryData.exchangeRateList.forEach((currencyData) => {
              let option = document.createElement("option");
              option.value = currencyData[0];
              option.textContent = currencyData[0];
              option.setAttribute("data-rate", currencyData[1]);
              currencySelect.appendChild(option);
            });

            Array.from(currencySelect.options).forEach((option) => {
              if (option.value === countryData.currentCurrencyCode) {
                currencySelect.value = option.value;
              }
              updateConvertedAmount();
            });

            // console.log("Data received:", countryData);

            focusCountry(selectedCountry);

            if (window.currentAirportLayer) {
              map.removeLayer(window.currentAirportLayer);
              layerControl.removeLayer(window.currentAirportLayer);
            }

            const airportLayer = L.layerGroup();
            var markerCluster = L.markerClusterGroup({
              polygonOptions: {
                fillColor: "#fff",
                color: "#000",
                weight: 2,
                opacity: 1,
                fillOpacity: 0.5,
              },
            });

            if (
              countryData.iataAirports &&
              countryData.iataAirports.length > 0
            ) {
              countryData.iataAirports.forEach((airport) => {
                const marker = L.marker([airport.latitude, airport.longitude], {
                  icon: airportIcon,
                });
                marker.bindPopup(`<b>${airport.name}</b>`);
                markerCluster.addLayer(marker);
              });
            }
            window.currentAirportLayer = markerCluster;
            map.addLayer(markerCluster);
            layerControl.addOverlay(markerCluster, "Airports");

            if (window.currentCountryBorder) {
              map.removeLayer(window.currentCountryBorder);
            }

            const borderResponse = await fetch(
              "libs/php/getCountryBorder.php",
              {
                method: "POST",
                headers: {
                  "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `countryName=${encodeURIComponent(selectedCountry)}`,
              }
            );
            const borderData = await borderResponse.json();

            if (borderData.bordersArray && borderData.bordersArray.length > 0) {
              window.currentCountryBorder = L.geoJSON(
                borderData.bordersArray
              ).addTo(map);
              const bounds = window.currentCountryBorder.getBounds();
              if (bounds.isValid()) {
                map.fitBounds(bounds);
              } else {
                console.error("Bounds are not valid:", bounds);
              }
            } else {
              console.error(
                "Invalid or empty borders array:",
                borderData.bordersArray
              );
            }
          } else {
            console.error("Missing data:", countryData);
          }
        };

        document
          .getElementById("countrySelect")
          .addEventListener("change", function () {
            handleCountryChange(this.value);
          });
      } else {
        console.error("Error fetching country borders data.");
      }
    })
    .catch((error) => {
      console.error("Error fetching country borders data:", error);
    });
});
