import { library, config } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { 
  // Icônes d'interface de base
  faUser,
  faUsers,
  faHome,
  faChartLine,
  faCog,
  faSignOutAlt,
  faSignInAlt,
  faUserPlus,
  faEdit,
  faTrash,
  faPlus,
  faMinus,
  faSave,
  faTimes,
  faCheck,
  faChevronLeft,
  faChevronRight,
  faChevronUp,
  faChevronDown,
  faArrowLeft,
  faArrowRight,
  faBars,
  faSearch,
  
  // Icônes d'activités sportives
  faRunning,
  faDumbbell,
  faMusic,
  faChild,
  
  // Icônes utilitaires
  faClock,
  faCalendar,
  faCalendarAlt,
  faMapMarkerAlt,
  faPhone,
  faEnvelope,
  faGlobe,
  faInfoCircle,
  faExclamationTriangle,
  faCheckCircle,
  faTimesCircle,
  faSpinner,
  
  // Icônes financières
  faEuroSign,
  faMoneyBillWave,
  faCreditCard,
  
  // Icônes de gestion
  faBuilding,
  faUserTie,
  faGraduationCap,
  faBook,
  faBookOpen,
  faFileAlt,
  faClipboardList,
  
  // Icônes de temps
  faHourglass,
  faStopwatch,
  
  // Icônes sociales et communication
  faBell,
  faComment,
  faComments,
  faShare,
  
  // Icônes de statut
  faCircle,
  faSquare,
  faStar,
  faHeart,
  faThumbsUp,
  faThumbsDown,
  
  // Icônes spécialisées
  faSwimmer,
  faHorse,
  faFistRaised,
  faTableTennis,
  faLightbulb,
  faSyncAlt,
  faFutbol,
  
} from '@fortawesome/free-solid-svg-icons'

// Désactiver l'ajout automatique de CSS
config.autoAddCss = false

// Ajouter les icônes à la bibliothèque
library.add(
  // Interface
  faUser,
  faUsers,
  faHome,
  faChartLine,
  faCog,
  faSignOutAlt,
  faSignInAlt,
  faUserPlus,
  faEdit,
  faTrash,
  faPlus,
  faMinus,
  faSave,
  faTimes,
  faCheck,
  faChevronLeft,
  faChevronRight,
  faChevronUp,
  faChevronDown,
  faArrowLeft,
  faArrowRight,
  faBars,
  faSearch,
  
  // Activités
  faRunning,
  faDumbbell,
  faMusic,
  faChild,
  faSwimmer,
  faHorse,
  faFistRaised,
  faTableTennis,
  
  // Utilitaires
  faClock,
  faCalendar,
  faCalendarAlt,
  faMapMarkerAlt,
  faPhone,
  faEnvelope,
  faGlobe,
  faInfoCircle,
  faExclamationTriangle,
  faCheckCircle,
  faTimesCircle,
  faSpinner,
  
  // Financier
  faEuroSign,
  faMoneyBillWave,
  faCreditCard,
  
  // Gestion
  faBuilding,
  faUserTie,
  faGraduationCap,
  faBook,
  faBookOpen,
  faFileAlt,
  faClipboardList,
  
  // Temps
  faHourglass,
  faStopwatch,
  
  // Social
  faBell,
  faComment,
  faComments,
  faShare,
  
  // Statut
  faCircle,
  faSquare,
  faStar,
  faHeart,
  faThumbsUp,
  faThumbsDown,
  
  // Supplémentaires
  faLightbulb,
  faSyncAlt,
  faFutbol,
)

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.component('font-awesome-icon', FontAwesomeIcon)
})