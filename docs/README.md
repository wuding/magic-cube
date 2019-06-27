# MagicCube - Module & Controller



## 实现过程

HTTP 请求方法和请求地址转换标准化后的句柄



## 路由处理结果

- 状态：0 未找到 1 成功匹配 2 方法不允许
- 方法：全转为小写
- 标识：<method>:<module>/<controller>@<action>



## 控制器路径和方法调用

- 模块文件夹：是否放 module 目录里

- 方法文件夹

- 方法附加到动作名：

  ```
  <prefix><action><suffix>
  ```

- 方法地图：

  ```
  $actionMaps = [
      'actionName' => [
          'get' => 'index',
          'post' => 'create',
      ],
  ];
  ```



## 预置动作

- 未找到：_notfound
- 缺省：_default



## 解析优先级

| 模块    | 方法    | 控制器             | 动作              |              |
| ------- | ------- | ------------------ | ----------------- | ------------ |
| module  | method  | controler          | action            | 完全匹配     |
| module  | _method | controler          | action            | 方法未定义   |
| module  |         | controler          | _action           | 动作未定义   |
| module  |         | _controller        | controler,_action | 控制器未定义 |
| _module |         | module,_controller | controler,_action | 模块未定义   |

