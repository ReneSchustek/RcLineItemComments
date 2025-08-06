// Import the plugin
import LineItemCommentsPlugin from './line-item-comments/line-item-comments.plugin';

// Register the plugin
const PluginManager = window.PluginManager;
PluginManager.register('LineItemComments', LineItemCommentsPlugin, '[data-line-item-comments]');