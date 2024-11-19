<script setup>
import { ref, reactive } from 'vue';
import { watchEffect } from 'vue';

const stock = reactive({
  length: 6000, // Longueur du stock en mm
  width: 50,    // Largeur du stock en mm
  quantity: 1,  // Quantité de stock (nombre de barres disponibles)
});

const pieces = ref([]);
const nestingSvgs = ref([]); // Stocke les SVGs générés pour chaque imbrication (chaque barre)
const notNestedPieces = ref([]); // Pièces non imbriquées à cause du manque de stock

const startOffset = ref(10); // Offset initial
const pieceOffset = ref(10); // Offset entre les pièces

// Ajouter une pièce avec une couleur aléatoire par défaut
let pieceCount = 1;
function addPiece() {
  pieces.value.push({
    label: `Pièce #${pieceCount}`,
    length: 0,
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



function placePiecesInStock() {
  let remainingStock = stock.length * stock.quantity; // Stock restant disponible (global)
  let svgResults = [];
  let remainingPieces = [...pieces.value]; // Les pièces restantes à imbriquer
  let currentBarStock = stock.length; // Stock disponible pour une barre actuelle
  let currentOffsetX = startOffset.value; // L'offset commence à la valeur d'origine

  // Réinitialiser les pièces non imbriquées
  notNestedPieces.value = [];


  while (remainingPieces.length > 0 && remainingStock > 0) {
  
    watchEffect(() => {
      console.log('remainingStock:', remainingStock);
      console.log('remainingPieces:', remainingPieces);
    });

    if (remainingStock < remainingPieces.length) break; // Stopper si plus de stock global
    if (remainingStock < stock.length) break; // Stopper si stock insuffisant pour une barre

    let usedLength = 0; // Longueur utilisée de la barre actuelle
    
    let currentSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100" viewBox="0 0 ${stock.length} ${stock.width}">`;
    currentSvg += `<rect x="0" y="0" width="${stock.length}" height="${stock.width}" fill="lightgrey" stroke="black" stroke-width="2" />`;

    let svgDetails = [];

    // Trier les pièces par taille (du plus grand au plus petit)
    const sortedPieces = remainingPieces.sort((a, b) => b.length - a.length);
    let newRemainingPieces = [];

    sortedPieces.forEach(piece => {
      let quantityPlaced = 0;

      for (let i = 0; i < piece.quantity; i++) {
        // Si la pièce ne tient pas dans la barre, on arrête
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

        // Ajouter la pièce dans la barre (SVG)
        currentSvg += `<rect x="${currentOffsetX}" y="0" width="${piece.length}" height="${stock.width}" fill="${piece.color}" stroke="black" stroke-width="1" />`;
        let textX = currentOffsetX + (piece.length / 2);
        currentSvg += `<text x="${textX}" y="${stock.width / 2}" font-size="${stock.width / 2}" text-anchor="middle" alignment-baseline="middle" fill="black">${piece.label} (${piece.length}mm)</text>`;

        // Mettre à jour la position et les stocks
        currentOffsetX += piece.length + pieceOffset.value;
        quantityPlaced++;
        currentBarStock -= piece.length;
        remainingStock -= piece.length;
        usedLength += piece.length; 
        svgDetails.push(`${piece.label} : ${quantityPlaced}/${piece.quantity}`);
      }

      // Si des pièces n'ont pas pu être imbriquées, on les ajoute à newRemainingPieces
      if (quantityPlaced < piece.quantity) {
        // Rechercher si la pièce existe déjà dans newRemainingPieces
        let existingPiece = newRemainingPieces.find(p => p.label === piece.label && p.length === piece.length);
        if (existingPiece) {
          // Si la pièce existe, on met à jour la quantité restante
          existingPiece.quantity = piece.quantity - quantityPlaced;
          
          //console.log('piece.quantity:', piece.quantity);
          //console.log('quantityPlaced:', quantityPlaced);
          //console.log('existingPiece.quantity:', existingPiece.quantity);
        } else {
          // Sinon, on ajoute une nouvelle entrée avec la quantité restante
          newRemainingPieces.push({ ...piece, quantity: piece.quantity - quantityPlaced });
        }
      }
    });

    currentSvg += `</svg>`;

    // Calculer le taux d'utilisation de la barre
    let usageRate = (usedLength / stock.length) * 100;

    svgResults.push({
      svg: currentSvg,
      totalLength: stock.length,
      usageRate: usageRate.toFixed(2),
      details: svgDetails,
    });

    // Réinitialiser les variables pour la prochaine barre
    currentOffsetX = startOffset.value;
    currentBarStock = stock.length;
    // Mettre à jour remainingPieces avec les pièces restantes
    remainingPieces = newRemainingPieces;
  }

  // Filtrer les pièces avec des quantités non nulles
  notNestedPieces.value = remainingPieces.filter(piece => piece.quantity > 0);
  nestingSvgs.value = svgResults;
}

// Soumettre les données et générer les imbrications
function submitData() {
  nestingSvgs.value = [];
  notNestedPieces.value = [];
  placePiecesInStock();
}

// Télécharger tous les SVGs générés
function downloadAllSVGs() {
  nestingSvgs.value.forEach((svgResult, index) => {
    const blob = new Blob([svgResult.svg], { type: 'image/svg+xml' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `nesting-result-${index + 1}.svg`;
    link.click();
  });
}
</script>

<template>
  <div id="app">
    <h1>Imbrication de profils</h1>
    <form @submit.prevent="submitData">
      <div class="form-group" style="display: flex; flex-direction: column; gap: 20px;">
        <div style="display: flex;">
          <div style="flex: 1;">
            <label for="stock.idth">Largeur :</label>
            <input v-model="stock.width" type="text" />
          </div>
          <div style="flex: 1; margin-right: 10px;">
            <label for="stock.length">Longueur du stock (mm) :</label>
            <input v-model="stock.length" type="number" required />
          </div>
          <div style="flex: 1; margin-right: 10px;">
            <label for="stock.length">Quantité de stock :</label>
            <input v-model="stock.quantity" type="number" required />
          </div>
        </div>
      </div>


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
      <hr>
      <div class="form-group">
        <label for="startOffset">Offset de début (mm) :</label>
        <input v-model="startOffset" type="number" required />
      </div>

      <div class="form-group">
        <label for="pieceOffset">Offset entre les pièces (mm) :</label>
        <input v-model="pieceOffset" type="number" required />
      </div>

      <button type="submit">Générer le Nesting</button>
    </form>

    <div v-if="nestingSvgs.length > 0" style="display: flex; justify-content: space-between;">
      <div style="flex: 1; margin-right: 10px;">
        <button @click="printContent">Imprimer le contenu</button>
      </div>
      <div style="flex: 1; margin-right: 10px;">
        <button @click="downloadAllSVGs">Télécharger les SVGs</button>
      </div>
    </div>

    <div v-if="nestingSvgs.length > 0" class="nesting-container" ref="printableContent">
      <h2 class="title">Résultat du Nesting :</h2>
      <div v-for="(svgResult, index) in nestingSvgs" :key="index" class="nesting-item">
        <!-- Ajouter un titre pour chaque barre -->
        <div class="bar-title">
          <h3>Barre {{ index + 1 }} - x: {{ stock.length }} mm, y: {{ stock.width }} mm</h3>
          <p class="usage-rate">Taux d'utilisation de la barre : {{ svgResult.usageRate }}%</p>
        </div>
        <div class="svg-container" v-html="svgResult.svg"></div>
        <div class="details">
          <p class="quantity-title">Quantités imbriquées :</p>
          <span class="quantity-details" v-html="svgResult.details.join('<br>')"></span>
        </div>
      </div>
    </div>

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
  font-family: Arial, sans-serif;
  padding: 20px;
}

h1 {
  font-size: 2rem;
  color: #333;
}

h2 {
  font-size: 1.5rem;
  margin-top: 20px;
}

form {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 20px;
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
  background-color: #45a049;
}

.piece-form {
  display: flex;
  gap: 10px;
  align-items: center;
}

.piece-form input {
  width: 150px;
}

button[type="button"] {
  background-color: #e53935;
}

button[type="button"]:hover {
  background-color: #c62828;
}

button[type="submit"] {
  background-color: #2196F3;
}

button[type="submit"]:hover {
  background-color: #1976D2;
}
.color-picker {
  border: none; /* Enlève la bordure */
  width: 40px;   /* Taille pour rendre le champ carré */
  height: 40px;  /* Assure que c'est un carré */
  padding: 0;
  cursor: pointer; /* Ajoute un curseur pour indiquer que c'est cliquable */
}
.nesting-container {
  background-color: #f9f9f9;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin: 20px auto;
}

.title {
  font-size: 1.8rem;
  font-weight: 600;
  color: #333;
  text-align: center;
  margin-bottom: 20px;
}

.nesting-item {
  background-color: #fff;
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
  color: #555;
}

.quantity-title {
  font-weight: bold;
  color: #007BFF;
}

.quantity-details {
  font-size: 0.95rem;
  line-height: 1.5;
}

.divider {
  margin-top: 15px;
  border: 1px solid #e0e0e0;
}

@media print {
  button {
    display: none;
  }
}
</style>