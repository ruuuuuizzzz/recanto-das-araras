const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/app.js', 'utf8');
const start = source.indexOf('    const resident=document.getElementById');
const end = source.indexOf("    document.querySelectorAll('.grupoHeader')", start);
function setup(block='', apartment='', selected='') {
    const field = value => ({value, listeners:{}, addEventListener(type, callback){this.listeners[type]=callback;}});
    const options = [
        {value:'',dataset:{}},
        {value:'1',dataset:{bloco:'1',apartamento:'101'}},
        {value:'2',dataset:{bloco:'2',apartamento:'101'}},
        {value:'3',dataset:{bloco:'2',apartamento:'101'}},
        {value:'4',dataset:{bloco:'3',apartamento:'A12'}}
    ];
    const resident = Object.assign(field(selected), {options, after(){}, setAttribute(){}, getAttribute(){return null;}, get selectedOptions(){return options.filter(option=>option.value===this.value);}});
    const fields = {bloco:field(block),apartamento:field(apartment)};
    const document = {getElementById(){return resident;},querySelector(selector){return fields[selector.includes('bloco')?'bloco':'apartamento'];},createElement(){return {setAttribute(){},style:{}};}};
    vm.runInNewContext(source.slice(start,end),{document});
    return {resident,fields,edit(name,value){fields[name].value=value;fields[name].listeners.input();}};
}
let form=setup();
form.edit('bloco','1');form.edit('apartamento','101');
assert.equal(form.resident.value,'1','Seleciona o único morador da unidade');
form.edit('bloco','2');
assert.equal(form.resident.value,'','Limpa seleção anterior ao trocar unidade');
assert.equal(form.resident.options[1].disabled,true);
assert.equal(form.resident.options[2].disabled,false);
form.resident.value='3';form.resident.listeners.change();
assert.equal(form.resident.value,'3','Preserva escolha entre múltiplos moradores');
form.edit('apartamento','999');
assert.equal(form.resident.value,'','Limpa seleção quando não existe morador');
assert.ok(form.resident.options.slice(1).every(option=>option.disabled));
form.edit('apartamento','');
assert.ok(form.resident.options.slice(1).every(option=>!option.disabled),'Permite escolha manual com unidade incompleta');
form=setup('1','101','1');assert.equal(form.resident.value,'1','Preserva morador em edição');
form=setup('1','101');assert.equal(form.resident.value,'1','Preenche unidade recebida pela URL');
form=setup(' 3 ',' a12 ');assert.equal(form.resident.value,'4','Ignora espaços e diferenças de maiúsculas');
form=setup('2','101','3');assert.equal(form.resident.value,'3','Preserva segundo morador em edição');
console.log('OK: preenchimento, troca de unidade, múltiplos moradores, unidade inexistente, edição e normalização.');
