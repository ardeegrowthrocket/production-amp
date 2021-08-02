if(Validation){
    Validation.add('validate-data-amp','Please use only letters (a-z or A-Z), numbers (0-9) or dash(-) in this field. Should not start and end with dash(-)',function(v){
        if(v != '' && v) {
            return /^[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/.test(v);
        }
    });
}