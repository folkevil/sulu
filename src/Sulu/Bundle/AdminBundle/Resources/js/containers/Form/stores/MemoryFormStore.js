// @flow
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import Ajv from 'ajv';
import jsonpointer from 'json-pointer';
import type {FormStoreInterface, RawSchema} from '../types';
import AbstractFormStore from './AbstractFormStore';

const ajv = new Ajv({allErrors: true, jsonPointers: true});

export default class MemoryFormStore extends AbstractFormStore implements FormStoreInterface {
    id = undefined;
    options = {};
    resourceKey = undefined;
    @observable data: {[string]: any};
    @observable dirty: boolean = false;
    updateFieldPathEvaluationsDisposer: ?() => void;

    constructor(
        data: {[string]: any},
        rawSchema: RawSchema,
        jsonSchema: ?Object,
        locale: ?IObservableValue<string>,
        metadataOptions: ?{[string]: any}
    ) {
        super();

        this.data = data;
        this.loading = false;
        this.rawSchema = rawSchema;
        this.locale = locale;
        this.addMissingSchemaProperties();
        this.validator = jsonSchema ? ajv.compile(jsonSchema) : undefined;
        this.metadataOptions = metadataOptions;

        this.updateFieldPathEvaluationsDisposer = autorun(this.updateFieldPathEvaluations);
    }

    @action change(path: string, value: mixed) {
        jsonpointer.set(this.data, '/' + path, value);
        this.dirty = true;
    }

    @action setMultiple(data: Object) {
        this.data = data;

        super.setMultiple();
    }

    destroy() {
        if (this.updateFieldPathEvaluationsDisposer) {
            this.updateFieldPathEvaluationsDisposer();
        }
    }
}
