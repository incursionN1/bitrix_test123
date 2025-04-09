<!--<pre>--><?php //=print_r($component->getName(),1)?><!--</pre>-->
<!--<pre>--><?php //=print_r(json_encode($arResult['DEAL'],JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),1)?><!--</pre>-->
<div id="app">
    <p>Сделки</p>
    <button @click="styleSwitch()">{{styleDeal}} </button>
    <button @click="submitForm()">{{buttonUpdate}} </button>
    <br>
    <table class="table" v-show="isStyleDeal"  v-for="categorie in countCategories">
        <thead>
        <tr>
             <td class="table-title" colspan="100%"> {{nameCategories[categorie]}} </td>
        </tr>
        <tr>
            <td  v-for="head in heads[categorie]" :class="head" :id="head+categorie" > {{head}} </td>
        </tr>
        </thead>
        <tr v-for="deal in deals[categorie]" >
            <td> {{deal.ID}} </td>
            <td> {{deal.CREATED_TIME}} </td>
            <td> {{deal.CREATED_BY}} </td>
            <td> {{deal.UPDATED_BY}} </td>
            <td> {{deal.ASSIGNED_BY_ID}} </td>
            <td> {{deal.COMPANY_ID}} </td>
            <td> {{deal.CONTACT_ID}} </td>
            <td> {{deal.TITLE}} </td>
            <td> {{deal.STAGE_ID}} </td>
            <td> {{deal.OPPORTUNITY}} </td>
        </tr>
    </table>

    <table class="tablel_card" v-show="!isStyleDeal"  v-for="categorie in countCategories">
        <thead>
        <tr>
            <td  class="table-title" colspan="100%"> {{nameCategories[categorie]}} </td>
        </tr>
        <tr>
            <td  v-for="statage in statageCategories[categorie]"> {{statage.NAME}} </td>
        </tr>
        </thead>
        <tr>
         <td v-for="items in dealStatages[categorie]" >
                 <div class="deal-card" v-for="item in items" >
                     <div class="deal-card-header">
                         <h2 class="deal-card-title">{{item.TITLE}}</h2>
                         <span class="deal-card-status"></span>
                     </div>

                     <div class="deal-card-body">
                         <div class="deal-card-row">
                             <span class="deal-card-label">ID: </span>
                             <span class="deal-card-value">{{item.ID}}</span>
                         </div>

                         <div class="deal-card-row">
                             <span class="deal-card-label">Сумма: </span>
                             <span class="deal-card-value">{{item.OPPORTUNITY}}</span>
                         </div>

                         <div class="deal-card-row">
                             <span class="deal-card-label">Дата создания: </span>
                             <span class="deal-card-value">{{item.CREATED_TIME}}</span>
                         </div>

                         <div class="deal-card-row">
                             <span class="deal-card-label">Ответственный: </span>
                             <span class="deal-card-value">{{item.ASSIGNED_BY_ID}}</span>
                         </div>

                         <div class="deal-card-row">
                             <span class="deal-card-label">Компания: </span>
                             <span class="deal-card-value">{{item.COMPANY_ID}}</span>
                         </div>

                         <div class="deal-card-row">
                             <span class="deal-card-label">Контакт: </span>
                             <span class="deal-card-value">{{item.CONTACT_ID}}</span>
                         </div>
                     </div>

                     <div class="deal-card-footer">
                         <span class="deal-card-created-by">Создано пользователем : {{item.CREATED_BY}}</span>
                         <span class="deal-card-updated-by">Обновлено пользователем: {{item.UPDATED_BY}}</span>
                     </div>
                 </div>
        </td>
        </tr>
    </table>

</div>

<script>
    BX.ready(() => {
        if (BX.Vue3) {
            const app = BX.Vue3.createApp({
                data() {
                    return { message:           'Vue3 в Bitrix!',
                            deals:              JSON.parse('<?echo  json_encode($arResult['DEAL'], JSON_UNESCAPED_UNICODE) ?>'),
                            heads:              JSON.parse('<?echo  json_encode($arResult['HEADER'], JSON_UNESCAPED_UNICODE) ?>'),
                            countCategories:    Array.from({ length: <?echo  json_encode($arResult['COUNT_CATEGORIES']) ?> }, (_, i) => i),
                            nameCategories:     JSON.parse('<?echo  json_encode($arResult['CATEGORIES_NAME'],JSON_UNESCAPED_UNICODE) ?>') ,
                            statageCategories:  JSON.parse('<?echo  json_encode($arResult['CATEGORIES_STATAGES'], JSON_UNESCAPED_UNICODE) ?>') ,
                            dealStatages:       JSON.parse('<?echo  json_encode($arResult['DEAL_STATAGE'], JSON_UNESCAPED_UNICODE) ?>') ,
                            styleDeal:          'Карточка',
                            countStatage:       -1,
                            isStyleDeal:        true,
                            isLoader:           true,
                            buttonUpdate:       'Обновить'
                    }
                },
                mounted() {
                     document.querySelectorAll('.TITLE').forEach(el => {
                         el.addEventListener('click', () => this.handleHeadClick(el.id));
                    });

                },

                methods: {
                    handleHeadClick(id) {
                        if (document.querySelector('#'+id).className == 'TITLE_asc') {
                            document.querySelector('#'+id).className = 'TITLE_desc';
                            this.deals[id.split("TITLE")[1]].sort((a, b) => a.TITLE.localeCompare(b.TITLE));
                        }
                        else {
                            document.querySelector('#'+id).className ='TITLE_asc';
                            this.deals[id.split("TITLE")[1]].sort((a, b) => b.TITLE.localeCompare(a.TITLE));
                        }
                    },
                    styleSwitch ()  {
                       if (this.isStyleDeal)
                       {
                           this.isStyleDeal=false
                           this.styleDeal = 'Таблица'
                       }else {
                           this.isStyleDeal=true
                           this.styleDeal = 'Карточка'
                       }
                    },
                    submitForm() {
                        this.isLoader = true;

                        BX.ajax.runComponentAction(
                            '<?=$component->getName()?>',
                            'ajax',
                            {
                                mode: 'class',
                                data: {
                                },
                            }
                        ).then(response => {
                            console.log(response.data.data.DEAL);
                                this.deals=response.data.data.DEAL;
                                this.heads=response.data.data.HEADER;
                                this.countCategories=  Array.from({ length: response.data.data.COUNT_CATEGORIES }, (_, i) => i);
                                this.nameCategories=response.data.data.CATEGORIES_NAME;
                                this.statageCategories=response.data.data.CATEGORIES_STATAGES;
                                this.dealStatages=response.data.data.DEAL_STATAGE;
                                this.isLoader = false;
                        }).catch(error => {
                            console.log('danger', 'Ошибка: ' + (error.message || 'Неизвестная ошибка'));
                            this.isLoader = false;
                        });
                    },
                }
            }).mount('#app');
        }
    });
</script>