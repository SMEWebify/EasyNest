<script setup>
import { ref, reactive } from 'vue';
import { watchEffect } from 'vue';

const stocks = ref([]); // Stocke les différents profilés
const pieces = ref([]); // Stocke les pièces à imbriquer
const nestingSvgs = ref([]); // Stocke les SVGs générés pour chaque imbrication (chaque profilé)
const notNestedPieces = ref([]); // Pièces non imbriquées à cause du manque de stock
const barRemnants = ref([]); // Stocker les chutes de chaque barre
const startOffset = ref(10); // Offset initial
const pieceOffset = ref(10); // Offset entre les pièces

// Ajouter un profilé avec une taille par défaut
function addStock() {
  stocks.value.push({
    length: 6000, // Longueur par défaut en mm
    width: 100,    // Largeur par défaut en mm
    quantity: 1,  // Quantité de stock
  });
}

// Supprimer un profilé
function removeStock(index) {
  stocks.value.splice(index, 1);
}

// Ajouter une pièce avec une couleur aléatoire
let pieceCount = 1;
function addPiece() {
  pieces.value.push({
    label: `Pièce #${pieceCount}`,
    length: 100,
    quantity: 1,
    color: getRandomColor(),
  });
  pieceCount++;
}

// Supprimer une pièce
function removePiece(index) {
  pieces.value.splice(index, 1);
}

// Générer une couleur aléatoire
function getRandomColor() {
  const letters = '0123456789ABCDEF';
  let color = '#';
  for (let i = 0; i < 6; i++) {
    color += letters[Math.floor(Math.random() * 16)];
  }
  return color;
}

// Fonction principale pour imbriquer les pièces dans les stocks
function placePiecesInStock() {
  let remainingPieces = [...pieces.value]; // Copie des pièces restantes à imbriquer
  nestingSvgs.value = [];
  notNestedPieces.value = [];
  barRemnants.value = [];

  // Parcourir chaque stock pour imbriquer les pièces
  stocks.value.forEach((stock, stockIndex) => {
    let remainingStock = stock.length * stock.quantity; // Stock total disponible pour ce profilé
    let currentOffsetX = startOffset.value; // Position de départ pour chaque barre
    let svgResults = []; // Pour stocker les SVGs
    let currentBarStock = stock.length; // Stock actuel pour la barre en cours d'utilisation

    let barsUsed = 0;
    let usedLength = 0;

    while (remainingPieces.length > 0 && remainingStock > 0) {

      watchEffect(() => {
      console.log('remainingStock:', remainingStock);
      console.log('remainingPieces:', remainingPieces);
    });

    if (remainingStock < remainingPieces.length) break; // Stopper si plus de stock global
    if (remainingStock < stock.length) break; // Stopper si stock insuffisant pour une barre



      let currentSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100" viewBox="0 0 ${stock.length} ${stock.width}">`;
      currentSvg += `<rect x="0" y="0" width="${stock.length}" height="${stock.width}" fill="lightgrey" stroke="black" stroke-width="2" />`;

      let svgDetails = [];

      // Trier les pièces par longueur (du plus grand au plus petit)
      const sortedPieces = remainingPieces.sort((a, b) => b.length - a.length);
      let newRemainingPieces = [];

      sortedPieces.forEach(piece => {
        let quantityPlaced = 0;

        for (let i = 0; i < piece.quantity; i++) {
          // Si la pièce ne tient pas dans la barre actuelle, passer à la suivante
          if (currentOffsetX + piece.length > stock.length) {
          // Mettre à jour la quantité restante dans la même entrée
          let existingPiece = newRemainingPieces.find(p => p.label === piece.label && p.length === piece.length);
          if (existingPiece) {
            existingPiece.quantity += piece.quantity - quantityPlaced;
          } else {
            newRemainingPieces.push({ ...piece, quantity: piece.quantity - quantityPlaced });
          }
          break;
        }

          // Ajouter la pièce dans le SVG de la barre
          currentSvg += `<rect x="${currentOffsetX}" y="0" width="${piece.length}" height="${stock.width}" fill="${piece.color}" stroke="black" stroke-width="1" />`;
          let textX = currentOffsetX + (piece.length / 2);
          currentSvg += `<text x="${textX}" y="${stock.width / 2}" font-size="${stock.width / 2}" text-anchor="middle" alignment-baseline="middle" fill="black">${piece.label} (${piece.length}mm)</text>`;

          // Mettre à jour l'offset, le stock et le compteur de pièces placées
          currentOffsetX += piece.length + pieceOffset.value;
          quantityPlaced++;
          currentBarStock -= piece.length;
          remainingStock -= piece.length;
          usedLength += piece.length;
          svgDetails.push(`${piece.label} : ${quantityPlaced}/${piece.quantity}`);
        }

        if (quantityPlaced < piece.quantity) {
        // Rechercher si la pièce existe déjà dans newRemainingPieces
        let existingPiece = newRemainingPieces.find(p => p.label === piece.label && p.length === piece.length);
        if (existingPiece) {
         // Si la pièce existe, on met à jour la quantité restante
         existingPiece.quantity = piece.quantity - quantityPlaced;
        } else {
          // Sinon, on ajoute une nouvelle entrée avec la quantité restante
          newRemainingPieces.push({ ...piece, quantity: piece.quantity - quantityPlaced });
        }}
      });

      currentSvg += `</svg>`;
      let usageRate = (usedLength / stock.length) * 100;
      let barRemainder = stock.length - usedLength;

      svgResults.push({
        svg: currentSvg,
        totalLength: stock.length,
        usageRate: usageRate.toFixed(2),
        details: svgDetails,
      });

      barRemnants.value.push({
        bar: barsUsed + 1,
        remainder: barRemainder,
      });

      barsUsed++;
      currentOffsetX = startOffset.value;
      remainingPieces = newRemainingPieces;
    }

    nestingSvgs.value.push({
      stockIndex: stockIndex + 1,
      svgs: svgResults,
    });
  });

  notNestedPieces.value = remainingPieces.filter(piece => piece.quantity > 0);
}

// Soumettre les données et générer les imbrications
function submitData() {
  nestingSvgs.value = [];
  notNestedPieces.value = [];
  placePiecesInStock();
}

// Télécharger tous les SVGs générés
function downloadAllSVGs() {
  nestingSvgs.value.forEach((svgGroup, index) => {
    svgGroup.svgs.forEach((svgResult, svgIndex) => {
      const blob = new Blob([svgResult.svg], { type: 'image/svg+xml' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `nesting-result-stock-${svgGroup.stockIndex}-bar-${svgIndex + 1}.svg`;
      link.click();
    });
  });
}
</script>

<template>
  <div id="app">
    <h2>Imbrication de profils</h2>

    <h3>Profilés (Stocks)</h3>

    <button @click="addStock" type="button" class="button">Ajouter un profilé</button>
    <div v-for="(stock, index) in stocks" :key="index"  class="profil-form">
        <div style="flex: 1; margin-right: 10px;">
          <label for="stock.width">Largeur du profil :</label>
          <input v-model="stock.width" type="number" required />
        </div>
        <div style="flex: 1; margin-right: 10px;">
          <label for="stock.length">Longueur du stock (mm) :</label>
          <input v-model="stock.length" type="number" required />
        </div>
        <div style="flex: 1; margin-right: 10px;">
          <label for="stock.quantity">Quantité de stock :</label>
          <input v-model="stock.quantity" type="number" required />
        </div>
        <button @click="removeStock(index)" type="button">Supprimer le profilé</button>
    </div>

    <h3>Pièces</h3>

    <button @click="addPiece" type="button">Ajouter une pièce</button>
    <div v-for="(piece, index) in pieces" :key="index" class="piece-form">
      <div class="form-group">
        <label>Label de la pièce :</label>
        <input v-model="piece.label" type="text" required />
      </div>
      <div class="form-group">
        <label>Longueur (mm) :</label>
        <input v-model="piece.length" type="number" required />
      </div>
      <div class="form-group">
        <label>Quantité :</label>
        <input v-model="piece.quantity" type="number" required />
      </div>
      <div class="form-group">
        <label>Couleur :</label>
        <input v-model="piece.color" type="color" class="color-picker" />
      </div>
      <button @click="removePiece(index)" type="button">Supprimer</button>
    </div>

    
    <h3>Ecarts</h3>

    <div class="piece-form">
      <div class="form-group">
        <label  for="startOffset">Offset de début (mm) :</label>
        <input v-model="startOffset" type="number" required />
      </div>
      <div class="form-group">
        <label for="pieceOffset">Offset entre les pièces (mm) :</label>
        <input v-model="pieceOffset" type="number" required />
      </div>
    </div>

    <button @click="submitData" type="button">Imbriquer les pièces dans les stocks</button>


    <hr>

    <div v-if="nestingSvgs.length > 0" style="display: flex; justify-content: space-between;">
      <div style="flex: 1; margin-right: 10px;">
        <button @click="printContent">Imprimer le contenu</button>
      </div>
      <div style="flex: 1; margin-right: 10px;">
        <button @click="downloadAllSVGs">Télécharger les SVGs</button>
      </div>
    </div>

    <div ref="printableContent">
      <h2>Résultats de l'imbrication</h2>
      <div v-for="(svgGroup, index) in nestingSvgs" :key="index" class="nesting-container" >
        <h3>Stock #{{ svgGroup.stockIndex }}</h3>
        <div v-for="(svgResult, svgIndex) in svgGroup.svgs" :key="svgIndex" class="nesting-item">
        <!-- Ajouter un titre pour chaque barre -->
          <h4>Barre {{ svgIndex + 1 }} - Utilisation : {{ svgResult.usageRate }}%</h4>
          <div class="svg-container" v-html="svgResult.svg"></div>
          
          <div class="details">
            <p class="quantity-title">Quantités imbriquées :</p>
            <ul>
              <li v-for="detail in svgResult.details" :key="detail">{{ detail }}</li>
            </ul>
          </div>
        </div>
      </div>

      <div v-if="nestingSvgs.length > 0" class="remants-container">
        <!-- Message si le stock est insuffisant -->
        <div v-if="notNestedPieces.length > 0">
          <p style="color: red;">Certaines pièces n'ont pas pu être imbriquées à cause du manque de stock :</p>
          <ul>
            <li v-for="(piece, index) in notNestedPieces" :key="index">
              {{ piece.label }} - Quantité non imbriquée : {{ piece.quantity }}
            </li>
          </ul>
        </div>
      </div>

      <div v-if="nestingSvgs.length > 0" class="remants-container">
        <h2>Chutes de barres</h2>
        <ul>
          <li v-for="remnant in barRemnants" :key="remnant.bar">Barre {{ remnant.bar }} : Chute de {{ remnant.remainder }} mm</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      nestingSvgs: [], // Remplace cela par tes données
    };
  },
  methods: {
    printContent() {
      const printableContent = this.$refs.printableContent;
      
      if (printableContent) {
        // Créer une fenêtre ou un iframe temporaire pour l'impression
        const printWindow = window.open('', '', 'width=600,height=400');
        
        // Injecter le contenu de la div à imprimer dans cette fenêtre
        printWindow.document.write('<html><head><title>Impression</title>');
        printWindow.document.write('<style>body{font-family: Arial, sans-serif;}</style>'); // Appliquer des styles si nécessaire
        printWindow.document.write('</head><body>');
        printWindow.document.write(printableContent.innerHTML);
        printWindow.document.write('</body></html>');
        
        // Fermer et imprimer
        printWindow.document.close();
        printWindow.print();
      }
    }
  }
}
</script>

<style scoped>
#app {
  padding: 20px;
  height: 100%;
  align-items: center;
  font-family: 'Roboto', sans-serif;
  background: linear-gradient(135deg, #0d0d0d 0%, #1e293b 100%);
  color: #fff;
  text-align: center;
}

h1 {
  font-size: 2rem;
  color: #333;
}

h2 {
  font-size: 1.5rem;
  margin-top: 20px;
}

h3 {
  font-size: 1.5rem;
  margin-top: 20px;
}



.form-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

input {
  padding: 8px;
  font-size: 14px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

button {
  background-color: #4CAF50;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 10px;
}

button:hover {
  background-color: #a7803a;
}

.piece-form {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 20px;
  border-radius: 8px;
  background-color: #7fc1ca;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin: 20px auto;
}

.piece-form input {
  width: 150px;
}

.profil-form {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 20px;
  border-radius: 8px;
  background-color: #9da59d;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin: 20px auto;
}

.profil-form input {
  width: 150px;
}



.color-picker {
  border: none; /* Enlève la bordure */
  width: 40px;   /* Taille pour rendre le champ carré */
  height: 40px;  /* Assure que c'est un carré */
  padding: 0;
  cursor: pointer; /* Ajoute un curseur pour indiquer que c'est cliquable */
}

.nesting-container {
  background-color: #000000;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin: 20px auto;
}

.remants-container {
  background-color: #000000;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin: 20px auto;
  text-align: left;
}

.title {
  font-size: 1.8rem;
  font-weight: 600;
  color: #333;
  text-align: center;
  margin-bottom: 20px;
}

.nesting-item {
  background-color: #1e293b;
  border-radius: 8px;
  padding: 15px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.svg-container {
  text-align: center;
  margin-bottom: 10px;
}

.details {
  font-size: 1rem;
  color: #ffffff;
  text-align: left;
}

.quantity-title {
  font-weight: bold;
  color: #007BFF;
}

@media print {
  button {
    display: none;
  }
}
</style>