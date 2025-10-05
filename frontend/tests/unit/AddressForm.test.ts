import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import AddressForm from '../../components/AddressForm.vue'

describe('AddressForm Component', () => {
    let wrapper

    beforeEach(() => {
        wrapper = mount(AddressForm, {
            props: {
                modelValue: {
                    street: '',
                    street_number: '',
                    postal_code: '',
                    city: '',
                    country: 'France'
                }
            }
        })
    })

    describe('Rendu de base', () => {
        it('devrait rendre le composant', () => {
            expect(wrapper.exists()).toBe(true)
        })

        it('devrait avoir la classe address-form', () => {
            expect(wrapper.find('.address-form').exists()).toBe(true)
        })

        it('devrait avoir une structure en grille', () => {
            expect(wrapper.html()).toContain('grid')
            expect(wrapper.html()).toContain('grid-cols-1')
        })

        it('devrait être responsive avec md:grid-cols-2', () => {
            expect(wrapper.html()).toContain('md:grid-cols-2')
        })

        it('devrait avoir une ligne avec md:grid-cols-3', () => {
            expect(wrapper.html()).toContain('md:grid-cols-3')
        })
    })

    describe('Champs du formulaire', () => {
        it('devrait afficher le champ Rue', () => {
            expect(wrapper.text()).toContain('Rue')
        })

        it('devrait afficher le champ Numéro', () => {
            expect(wrapper.text()).toContain('Numéro')
        })

        it('devrait afficher le champ Code postal', () => {
            expect(wrapper.text()).toContain('Code postal')
        })

        it('devrait afficher le champ Ville', () => {
            expect(wrapper.text()).toContain('Ville')
        })

        it('devrait afficher le champ Pays', () => {
            expect(wrapper.text()).toContain('Pays')
        })

        it('devrait avoir 5 inputs', () => {
            const inputs = wrapper.findAll('input[type="text"]')
            expect(inputs.length).toBe(5)
        })

        it('tous les inputs devraient avoir le type "text"', () => {
            const inputs = wrapper.findAll('input')
            inputs.forEach(input => {
                expect(input.attributes('type')).toBe('text')
            })
        })
    })

    describe('Labels', () => {
        it('devrait avoir un label pour chaque champ', () => {
            const labels = wrapper.findAll('label')
            expect(labels.length).toBe(5)
        })

        it('les labels devraient avoir les bonnes classes', () => {
            const label = wrapper.find('label')
            expect(label.classes()).toContain('block')
            expect(label.classes()).toContain('text-sm')
            expect(label.classes()).toContain('font-medium')
            expect(label.classes()).toContain('text-gray-700')
        })
    })

    describe('Placeholders par défaut', () => {
        it('devrait avoir un placeholder "Nom de la rue" pour Rue', () => {
            const streetInput = wrapper.findAll('input')[0]
            expect(streetInput.attributes('placeholder')).toBe('Nom de la rue')
        })

        it('devrait avoir un placeholder "Numéro" pour Numéro', () => {
            const numberInput = wrapper.findAll('input')[1]
            expect(numberInput.attributes('placeholder')).toBe('Numéro')
        })

        it('devrait avoir un placeholder "Code postal" pour Code postal', () => {
            const postalInput = wrapper.findAll('input')[2]
            expect(postalInput.attributes('placeholder')).toBe('Code postal')
        })

        it('devrait avoir un placeholder "Ville" pour Ville', () => {
            const cityInput = wrapper.findAll('input')[3]
            expect(cityInput.attributes('placeholder')).toBe('Ville')
        })

        it('devrait avoir un placeholder "Pays" pour Pays', () => {
            const countryInput = wrapper.findAll('input')[4]
            expect(countryInput.attributes('placeholder')).toBe('Pays')
        })
    })

    describe('Placeholders personnalisés', () => {
        it('devrait accepter un placeholder personnalisé pour la rue', () => {
            wrapper = mount(AddressForm, {
                props: {
                    modelValue: {},
                    streetPlaceholder: 'Ex: Avenue des Champs-Élysées'
                }
            })
            const streetInput = wrapper.findAll('input')[0]
            expect(streetInput.attributes('placeholder')).toBe('Ex: Avenue des Champs-Élysées')
        })

        it('devrait accepter un placeholder personnalisé pour le numéro', () => {
            wrapper = mount(AddressForm, {
                props: {
                    modelValue: {},
                    numberPlaceholder: 'Ex: 123'
                }
            })
            const numberInput = wrapper.findAll('input')[1]
            expect(numberInput.attributes('placeholder')).toBe('Ex: 123')
        })
    })

    describe('V-model et mise à jour', () => {
        it('devrait initialiser avec les valeurs du modelValue', () => {
            wrapper = mount(AddressForm, {
                props: {
                    modelValue: {
                        street: 'Rue de la Paix',
                        street_number: '10',
                        postal_code: '75001',
                        city: 'Paris',
                        country: 'France'
                    }
                }
            })

            const inputs = wrapper.findAll('input')
            expect((inputs[0].element as HTMLInputElement).value).toBe('Rue de la Paix')
            expect((inputs[1].element as HTMLInputElement).value).toBe('10')
            expect((inputs[2].element as HTMLInputElement).value).toBe('75001')
            expect((inputs[3].element as HTMLInputElement).value).toBe('Paris')
            expect((inputs[4].element as HTMLInputElement).value).toBe('France')
        })

        it('devrait émettre update:modelValue quand un champ change', async () => {
            const streetInput = wrapper.findAll('input')[0]
            await streetInput.setValue('Nouvelle rue')
            await streetInput.trigger('input')

            expect(wrapper.emitted('update:modelValue')).toBeTruthy()
        })

        it('devrait émettre les bonnes données', async () => {
            const streetInput = wrapper.findAll('input')[0]
            await streetInput.setValue('Rue Test')
            await streetInput.trigger('input')

            const emitted = wrapper.emitted('update:modelValue')
            expect(emitted).toBeTruthy()
            if (emitted) {
                const emittedData = emitted[0][0] as any
                expect(emittedData.street).toBe('Rue Test')
            }
        })

        it('devrait maintenir les autres valeurs lors de la mise à jour', async () => {
            wrapper = mount(AddressForm, {
                props: {
                    modelValue: {
                        street: 'Rue A',
                        street_number: '10',
                        postal_code: '75001',
                        city: 'Paris',
                        country: 'France'
                    }
                }
            })

            const cityInput = wrapper.findAll('input')[3]
            await cityInput.setValue('Lyon')
            await cityInput.trigger('input')

            const emitted = wrapper.emitted('update:modelValue')
            if (emitted) {
                const emittedData = emitted[0][0] as any
                expect(emittedData.city).toBe('Lyon')
                expect(emittedData.street).toBe('Rue A')
                expect(emittedData.postal_code).toBe('75001')
            }
        })
    })

    describe('Styles des inputs', () => {
        it('tous les inputs devraient avoir w-full', () => {
            const inputs = wrapper.findAll('input')
            inputs.forEach(input => {
                expect(input.classes()).toContain('w-full')
            })
        })

        it('tous les inputs devraient avoir les classes de padding', () => {
            const inputs = wrapper.findAll('input')
            inputs.forEach(input => {
                expect(input.classes()).toContain('px-4')
                expect(input.classes()).toContain('py-3')
            })
        })

        it('tous les inputs devraient avoir une bordure', () => {
            const inputs = wrapper.findAll('input')
            inputs.forEach(input => {
                expect(input.classes()).toContain('border')
                expect(input.classes()).toContain('border-gray-300')
            })
        })

        it('tous les inputs devraient avoir rounded-lg', () => {
            const inputs = wrapper.findAll('input')
            inputs.forEach(input => {
                expect(input.classes()).toContain('rounded-lg')
            })
        })

        it('tous les inputs devraient avoir focus:ring-2', () => {
            const inputs = wrapper.findAll('input')
            inputs.forEach(input => {
                expect(input.classes()).toContain('focus:ring-2')
                expect(input.classes()).toContain('focus:ring-blue-500')
            })
        })
    })

    describe('Valeur par défaut du pays', () => {
        it('devrait initialiser le pays à "France" par défaut', () => {
            wrapper = mount(AddressForm)
            expect(wrapper.vm.localAddress.country).toBe('France')
        })
    })

    describe('Réactivité', () => {
        it('devrait réagir aux changements du modelValue externe', async () => {
            await wrapper.setProps({
                modelValue: {
                    street: 'Nouvelle rue',
                    street_number: '20',
                    postal_code: '69000',
                    city: 'Lyon',
                    country: 'France'
                }
            })

            await wrapper.vm.$nextTick()

            const inputs = wrapper.findAll('input')
            expect((inputs[0].element as HTMLInputElement).value).toBe('Nouvelle rue')
            expect((inputs[3].element as HTMLInputElement).value).toBe('Lyon')
        })
    })

    describe('Accessibilité', () => {
        it('chaque label devrait être associé à un input', () => {
            const labels = wrapper.findAll('label')
            expect(labels.length).toBe(5)
        })

        it('les labels devraient avoir mb-1 pour l\'espacement', () => {
            const label = wrapper.find('label')
            expect(label.classes()).toContain('mb-1')
        })
    })

    describe('Gap et espacement', () => {
        it('devrait avoir gap-4 entre les éléments', () => {
            expect(wrapper.html()).toContain('gap-4')
        })

        it('devrait avoir mb-4 sur la première ligne', () => {
            expect(wrapper.html()).toContain('mb-4')
        })
    })
})
