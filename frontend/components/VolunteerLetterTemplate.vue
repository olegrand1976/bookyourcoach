<template>
  <div class="volunteer-letter bg-white p-8 text-gray-900" style="font-family: 'Times New Roman', serif; line-height: 1.6;">
    <!-- En-tête -->
    <div class="text-center mb-8">
      <h1 class="text-2xl font-bold mb-2">Note d'Information au Volontaire</h1>
      <p class="text-sm italic">(Conformément à la Loi du 3 juillet 2005 relative aux droits des volontaires)</p>
    </div>

    <!-- Introduction -->
    <div class="mb-6 text-sm">
      <p>
        La présente note vise à informer le volontaire des conditions de son engagement au sein de l'ASBL, 
        conformément aux obligations légales.
      </p>
      <p class="mt-2">
        Il est rappelé que la relation entre le volontaire et l'ASBL ne relève pas d'un contrat de travail. 
        Le volontariat est une activité exercée sans rémunération et sans obligation de prestation, 
        dans un but désintéressé.
      </p>
    </div>

    <!-- Parties -->
    <div class="mb-6">
      <h2 class="text-lg font-bold mb-3">Entre :</h2>
      
      <!-- L'ASBL -->
      <div class="ml-4 mb-4">
        <p class="font-bold">L'ASBL : {{ club.name }}</p>
        <p>Siège social : {{ getFullAddress(club) }}</p>
        <p v-if="club.company_number">Numéro BCE : {{ club.company_number }}</p>
        <p v-if="club.legal_representative_name && club.legal_representative_role">
          Représentée par : {{ club.legal_representative_name }}, {{ club.legal_representative_role }}
        </p>
        <p class="italic mt-1">(Ci-après "l'Organisation")</p>
      </div>

      <!-- Le Volontaire -->
      <div class="ml-4">
        <p class="font-bold">Et :</p>
        <p class="mt-1">Le/La Volontaire : {{ teacher.user?.name }}</p>
        <p v-if="getTeacherAddress(teacher)">Adresse : {{ getTeacherAddress(teacher) }}</p>
        <p class="italic mt-1">(Ci-après "le Volontaire")</p>
      </div>
    </div>

    <!-- 1. Informations sur l'Organisation -->
    <div class="mb-6">
      <h2 class="text-lg font-bold mb-2">1. Informations sur l'Organisation</h2>
      <p class="ml-4">
        L'Organisation est une Association Sans But Lucratif (ASBL). 
        Son but désintéressé (objectif social) est le suivant : 
        {{ club.description || '[Décrire la mission principale de votre ASBL]' }}
      </p>
    </div>

    <!-- 2. Assurances -->
    <div class="mb-6">
      <h2 class="text-lg font-bold mb-2">2. Assurances</h2>
      <p class="ml-4 mb-2">
        Pour couvrir les activités du Volontaire, l'Organisation a souscrit les assurances obligatoires suivantes :
      </p>
      
      <!-- RC -->
      <div class="ml-8 mb-3">
        <p class="font-semibold">Assurance en Responsabilité Civile (RC) :</p>
        <p class="ml-4 text-sm">
          Cette assurance couvre les dommages corporels ou matériels que le Volontaire pourrait causer 
          à des tiers (non-membres de l'ASBL) durant l'exercice de sa mission.
        </p>
        <p class="ml-4 mt-1" v-if="club.insurance_rc_company">
          Compagnie d'assurance : <span class="font-medium">{{ club.insurance_rc_company }}</span>
        </p>
        <p class="ml-4" v-if="club.insurance_rc_policy_number">
          Numéro de police : <span class="font-medium">{{ club.insurance_rc_policy_number }}</span>
        </p>
      </div>

      <!-- Assurance complémentaire -->
      <div v-if="club.insurance_additional_company" class="ml-8">
        <p class="font-semibold">Assurance complémentaire :</p>
        <p class="ml-4 mt-1">
          Compagnie d'assurance : <span class="font-medium">{{ club.insurance_additional_company }}</span>
        </p>
        <p class="ml-4" v-if="club.insurance_additional_policy_number">
          Numéro de police : <span class="font-medium">{{ club.insurance_additional_policy_number }}</span>
        </p>
        <p class="ml-4 text-sm" v-if="club.insurance_additional_details">
          {{ club.insurance_additional_details }}
        </p>
      </div>
    </div>

    <!-- 3. Régime des Défraiements -->
    <div class="mb-6">
      <h2 class="text-lg font-bold mb-2">3. Régime des Défraiements</h2>
      <p class="ml-4 mb-2">
        L'Organisation s'engage à rembourser les frais engagés par le Volontaire dans le cadre de sa mission, 
        selon les modalités suivantes :
      </p>

      <!-- Forfaitaire -->
      <div v-if="club.expense_reimbursement_type === 'forfait'" class="ml-4">
        <p class="font-semibold mb-2">Défraiement Forfaitaire</p>
        <p class="ml-4 text-sm" v-if="club.expense_reimbursement_details">
          {{ club.expense_reimbursement_details }}
        </p>
        <p class="ml-4 text-sm mt-1">
          Ce montant est réputé couvrir l'ensemble des frais du Volontaire, sans que celui-ci n'ait à fournir de justificatifs. 
          L'Organisation s'assure que les plafonds légaux (journaliers et annuels) fixés par la loi ne sont pas dépassés.
        </p>
      </div>

      <!-- Frais réels -->
      <div v-else-if="club.expense_reimbursement_type === 'reel'" class="ml-4">
        <p class="font-semibold mb-2">Remboursement des Frais Réels</p>
        <p class="ml-4 text-sm mb-1">
          L'Organisation s'engage à rembourser les frais réellement engagés par le Volontaire, 
          sur présentation des justificatifs originaux (tickets de transport, factures, souches TVA, etc.).
        </p>
        <p class="ml-4 text-sm" v-if="club.expense_reimbursement_details">
          {{ club.expense_reimbursement_details }}
        </p>
      </div>

      <!-- Aucun -->
      <div v-else class="ml-4">
        <p class="font-semibold">Absence de Défraiement</p>
        <p class="ml-4 text-sm mt-1">
          L'Organisation ne prévoit pas de système de remboursement pour les frais engagés par le Volontaire.
        </p>
      </div>
    </div>

    <!-- 4. Devoir de Discrétion -->
    <div class="mb-6">
      <h2 class="text-lg font-bold mb-2">4. Devoir de Discrétion et Confidentialité</h2>
      <p class="ml-4 mb-2">
        Le Volontaire est informé que, dans le cadre de ses activités, il peut avoir accès à des informations 
        confidentielles concernant l'Organisation, ses membres, ou ses bénéficiaires.
      </p>
      <p class="ml-4 mb-2">
        Le Volontaire s'engage à respecter un devoir de discrétion strict. Il s'interdit de divulguer ces informations 
        à des tiers, que ce soit pendant ou après la fin de son engagement volontaire.
      </p>
      <p class="ml-4 text-sm italic">
        Le Volontaire est informé qu'il est tenu au secret professionnel tel que défini par l'article 458 du Code pénal 
        concernant toutes les informations à caractère personnel dont il aurait connaissance.
      </p>
    </div>

    <!-- Déclaration du Volontaire -->
    <div class="mb-8">
      <h2 class="text-lg font-bold mb-2">Déclaration du Volontaire</h2>
      <p class="ml-4 mb-2">
        Le Volontaire atteste avoir reçu un exemplaire de cette note d'information, en avoir pris connaissance 
        et en accepter les termes avant le début de son activité de volontariat.
      </p>
      <p class="ml-4 text-sm">
        Le Volontaire est informé qu'il doit avertir son organisme de paiement (ONEM, mutuelle, CPAS) 
        s'il perçoit des allocations ou un revenu d'intégration, avant de débuter son activité.
      </p>
    </div>

    <!-- Signatures -->
    <div class="mt-12">
      <p class="text-center mb-8">
        Fait à <span class="font-medium">{{ club.city || '[Lieu]' }}</span>, 
        le <span class="font-medium">{{ getCurrentDate() }}</span>
      </p>
      <p class="text-center text-sm italic mb-8">
        En double exemplaire, chaque partie reconnaissant avoir reçu le sien.
      </p>

      <div class="grid grid-cols-2 gap-8 mt-12">
        <!-- Pour l'ASBL -->
        <div class="text-center">
          <p class="font-bold mb-1">Pour l'ASBL :</p>
          <p v-if="club.legal_representative_name">{{ club.legal_representative_name }}</p>
          <p v-if="club.legal_representative_role" class="text-sm italic">{{ club.legal_representative_role }}</p>
          <div class="mt-12 border-t border-gray-400 pt-2">
            <p class="text-sm">[Signature]</p>
          </div>
        </div>

        <!-- Pour le Volontaire -->
        <div class="text-center">
          <p class="font-bold mb-1">Pour le Volontaire :</p>
          <p class="text-sm italic">(Précédé de la mention manuscrite "Lu et approuvé")</p>
          <p class="mt-2">{{ teacher.user?.name }}</p>
          <div class="mt-12 border-t border-gray-400 pt-2">
            <p class="text-sm">[Signature]</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  club: {
    type: Object,
    required: true
  },
  teacher: {
    type: Object,
    required: true
  }
})

function getFullAddress(club) {
  const parts = []
  if (club.address) parts.push(club.address)
  if (club.postal_code && club.city) {
    parts.push(`${club.postal_code} ${club.city}`)
  } else if (club.city) {
    parts.push(club.city)
  }
  if (club.country) parts.push(club.country)
  return parts.join(', ') || '[Adresse de l\'ASBL]'
}

function getTeacherAddress(teacher) {
  const user = teacher.user
  if (!user) return ''
  
  const parts = []
  if (user.address) parts.push(user.address)
  if (user.postal_code && user.city) {
    parts.push(`${user.postal_code} ${user.city}`)
  } else if (user.city) {
    parts.push(user.city)
  }
  if (user.country) parts.push(user.country)
  return parts.join(', ')
}

function getCurrentDate() {
  const options = { year: 'numeric', month: 'long', day: 'numeric' }
  return new Date().toLocaleDateString('fr-FR', options)
}
</script>

<style scoped>
.volunteer-letter {
  font-size: 14px;
}

.volunteer-letter h1 {
  page-break-after: avoid;
}

.volunteer-letter h2 {
  page-break-after: avoid;
  margin-top: 1.5em;
}

@media print {
  .volunteer-letter {
    font-size: 12px;
  }
}
</style>

