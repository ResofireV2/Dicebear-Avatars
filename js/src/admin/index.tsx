import app from 'flarum/admin/app';
import * as avatarOptions from '@dicebear/collection';

app.initializers.add('resofire/dicebear', () => {
  const toKebabCase = (str: string) => {
    return str.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
  };

  const options = Object.keys(avatarOptions).reduce((acc: { [key: string]: string }, key) => {
    const kebabKey = toKebabCase(key);
    acc[kebabKey] = app.translator.trans(`resofire-dicebear.admin.avatar_style_options.${kebabKey}`).toString();
    return acc;
  }, {});

  app.extensionData
    .for('resofire-dicebear')
    .registerSetting({
      setting: 'resofire-dicebear.avatar_style',
      label: app.translator.trans('resofire-dicebear.admin.avatar_style'),
      help: app.translator.trans('resofire-dicebear.admin.avatar_style_help', {
        a: <a href="https://www.dicebear.com/styles/" />,
      }),
      type: 'select',
      options,
    })
    .registerSetting({
      setting: 'resofire-dicebear.api_url',
      label: app.translator.trans('resofire-dicebear.admin.api_url'),
      help: app.translator.trans('resofire-dicebear.admin.api_url_help'),
      type: 'text',
    });
});
